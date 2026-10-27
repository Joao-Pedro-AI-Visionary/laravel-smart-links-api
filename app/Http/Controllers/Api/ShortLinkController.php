<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreShortLinkRequest;
use App\Models\ShortLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ShortLinkController extends Controller
{
    public function index(): JsonResponse
    {
        $links = ShortLink::query()
            ->latest('id')
            ->paginate(20);

        return response()->json($links);
    }

    public function store(StoreShortLinkRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if (empty($validated['slug'])) {
            do {
                $candidate = Str::lower(Str::random(7));
            } while (ShortLink::query()->where('slug', $candidate)->exists());

            $validated['slug'] = $candidate;
        }

        $shortLink = ShortLink::query()->create($validated);

        return response()->json([
            'data' => [
                'id' => $shortLink->id,
                'slug' => $shortLink->slug,
                'title' => $shortLink->title,
                'short_url' => url("/r/{$shortLink->slug}"),
                'target_url' => $shortLink->target_url,
                'utm_source' => $shortLink->utm_source,
                'utm_medium' => $shortLink->utm_medium,
                'webhook_url' => $shortLink->webhook_url,
                'expires_at' => $shortLink->expires_at?->toIso8601String(),
                'clicks_count' => 0,
                'created_at' => $shortLink->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    public function stats(string $slug): JsonResponse
    {
        $shortLink = ShortLink::query()->where('slug', $slug)->firstOrFail();

        $uniqueVisitors = $shortLink->clicks()
            ->distinct('visitor_hash')
            ->count('visitor_hash');

        $byDeviceRaw = $shortLink->clicks()
            ->selectRaw('device_type, COUNT(*) as total')
            ->groupBy('device_type')
            ->pluck('total', 'device_type')
            ->all();

        $byRefererRaw = $shortLink->clicks()
            ->whereNotNull('referer_host')
            ->selectRaw('referer_host, COUNT(*) as total')
            ->groupBy('referer_host')
            ->orderByDesc('total')
            ->limit(10)
            ->pluck('total', 'referer_host')
            ->all();

        $recentClicks = $shortLink->clicks()
            ->latest('clicked_at')
            ->limit(10)
            ->get(['device_type', 'referer_host', 'clicked_at']);

        return response()->json([
            'data' => [
                'slug' => $shortLink->slug,
                'title' => $shortLink->title,
                'target_url' => $shortLink->target_url,
                'total_clicks' => (int) $shortLink->clicks_count,
                'unique_visitors' => (int) $uniqueVisitors,
                'by_device' => [
                    'desktop' => (int) ($byDeviceRaw['desktop'] ?? 0),
                    'mobile' => (int) ($byDeviceRaw['mobile'] ?? 0),
                    'bot' => (int) ($byDeviceRaw['bot'] ?? 0),
                ],
                'by_referer' => $byRefererRaw,
                'recent_clicks' => $recentClicks,
                'is_expired' => $shortLink->isExpired(),
            ],
        ]);
    }

    public function destroy(string $slug): JsonResponse
    {
        $shortLink = ShortLink::query()->where('slug', $slug)->firstOrFail();
        $shortLink->delete();

        return response()->json(null, 204);
    }
}
