<?php

namespace App\Services;

use App\Models\ShortLink;

class UrlDestinationBuilder
{
    public function build(ShortLink $link): string
    {
        $utmParams = array_filter([
            'utm_source' => $link->utm_source,
            'utm_medium' => $link->utm_medium,
        ], fn ($val) => $val !== null && $val !== '');

        if (empty($utmParams)) {
            return $link->target_url;
        }

        $separator = str_contains($link->target_url, '?') ? '&' : '?';

        return $link->target_url.$separator.http_build_query($utmParams);
    }

    public function detectDeviceType(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'desktop';
        }

        $ua = strtolower($userAgent);
        if (str_contains($ua, 'bot') || str_contains($ua, 'spider') || str_contains($ua, 'crawler')) {
            return 'bot';
        }

        if (str_contains($ua, 'iphone') || str_contains($ua, 'android') || str_contains($ua, 'mobile')) {
            return 'mobile';
        }

        return 'desktop';
    }

    public function extractRefererHost(?string $refererUrl): ?string
    {
        if (! $refererUrl) {
            return null;
        }

        $host = parse_url($refererUrl, PHP_URL_HOST);

        return is_string($host) ? strtolower($host) : null;
    }
}
