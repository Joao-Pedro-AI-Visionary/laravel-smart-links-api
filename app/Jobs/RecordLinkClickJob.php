<?php

namespace App\Jobs;

use App\Models\LinkClick;
use App\Models\ShortLink;
use App\Services\UrlDestinationBuilder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class RecordLinkClickJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $shortLinkId,
        public readonly string $visitorHash,
        public readonly ?string $userAgent,
        public readonly ?string $refererUrl,
        public readonly string $clickedAtIso,
    ) {}

    public function handle(UrlDestinationBuilder $builder): void
    {
        $shortLink = ShortLink::query()->find($this->shortLinkId);
        if (! $shortLink) {
            return;
        }

        $deviceType = $builder->detectDeviceType($this->userAgent);
        $refererHost = $builder->extractRefererHost($this->refererUrl);

        DB::transaction(function () use ($shortLink, $deviceType, $refererHost) {
            LinkClick::query()->create([
                'short_link_id' => $shortLink->id,
                'visitor_hash' => $this->visitorHash,
                'device_type' => $deviceType,
                'referer_host' => $refererHost,
                'user_agent' => $this->userAgent ? substr($this->userAgent, 0, 512) : null,
                'clicked_at' => $this->clickedAtIso,
            ]);

            $shortLink->increment('clicks_count');
        });

        if ($shortLink->webhook_url) {
            try {
                Http::timeout(3)->post($shortLink->webhook_url, [
                    'event' => 'link.clicked',
                    'slug' => $shortLink->slug,
                    'title' => $shortLink->title,
                    'target_url' => $shortLink->target_url,
                    'device_type' => $deviceType,
                    'referer_host' => $refererHost,
                    'clicked_at' => $this->clickedAtIso,
                ]);
            } catch (\Throwable) {
                // Non-blocking webhook notification
            }
        }
    }
}
