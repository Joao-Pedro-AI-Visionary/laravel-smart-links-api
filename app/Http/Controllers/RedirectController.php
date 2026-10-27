<?php

namespace App\Http\Controllers;

use App\Jobs\RecordLinkClickJob;
use App\Models\ShortLink;
use App\Services\UrlDestinationBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RedirectController extends Controller
{
    public function __invoke(Request $request, string $slug, UrlDestinationBuilder $builder): RedirectResponse
    {
        $shortLink = ShortLink::query()->where('slug', $slug)->firstOrFail();

        if ($shortLink->isExpired()) {
            throw new HttpException(410, 'Este link curto expirou.');
        }

        $ip = (string) ($request->ip() ?? '0.0.0.0');
        $userAgent = $request->userAgent();
        $referer = $request->headers->get('referer');
        $today = now()->toDateString();

        $visitorHash = hash('sha256', "{$ip}|{$userAgent}|{$today}");

        RecordLinkClickJob::dispatch(
            shortLinkId: $shortLink->id,
            visitorHash: $visitorHash,
            userAgent: $userAgent,
            refererUrl: $referer,
            clickedAtIso: now()->toIso8601String(),
        );

        return redirect()->away($builder->build($shortLink), 302);
    }
}
