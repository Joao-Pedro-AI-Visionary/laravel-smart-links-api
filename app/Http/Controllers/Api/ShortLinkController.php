<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreShortLinkRequest;
use App\Models\ShortLink;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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

    public function update(Request $request, string $slug): JsonResponse
    {
        $shortLink = ShortLink::query()->where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:160'],
            'slug' => [
                'sometimes',
                'required',
                'string',
                'alpha_dash',
                'min:3',
                'max:64',
                Rule::unique('short_links', 'slug')->ignore($shortLink->id),
            ],
            'target_url' => ['sometimes', 'required', 'url:http,https', 'max:2048'],
            'utm_source' => ['nullable', 'string', 'max:80'],
            'utm_medium' => ['nullable', 'string', 'max:80'],
            'webhook_url' => [
                'nullable',
                'url:http,https',
                'max:2048',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value)) {
                        return;
                    }
                    $host = strtolower((string) parse_url($value, PHP_URL_HOST));
                    if ($host === '' || in_array($host, ['localhost', '0.0.0.0', '::1'], true)) {
                        $fail('O webhook_url não pode apontar para hosts locais ou internos.');

                        return;
                    }
                    if (filter_var($host, FILTER_VALIDATE_IP)) {
                        $isPublicIp = filter_var(
                            $host,
                            FILTER_VALIDATE_IP,
                            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
                        );
                        if ($isPublicIp === false) {
                            $fail('O webhook_url não pode apontar para endereços IP privados ou reservados (proteção SSRF).');
                        }
                    }
                },
            ],
            'expires_at' => ['nullable', 'date'],
        ]);

        $shortLink->update($validated);

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
                'clicks_count' => (int) $shortLink->clicks_count,
                'updated_at' => $shortLink->updated_at?->toIso8601String(),
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
