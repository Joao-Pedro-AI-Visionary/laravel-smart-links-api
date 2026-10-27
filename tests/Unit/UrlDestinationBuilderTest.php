<?php

namespace Tests\Unit;

use App\Models\ShortLink;
use App\Services\UrlDestinationBuilder;
use PHPUnit\Framework\TestCase;

class UrlDestinationBuilderTest extends TestCase
{
    public function test_builds_destination_url_preserving_existing_query_parameters(): void
    {
        $builder = new UrlDestinationBuilder;

        $link = new ShortLink([
            'target_url' => 'https://example.com/jobs?role=pleno',
            'utm_source' => 'github',
            'utm_medium' => 'readme',
        ]);

        $this->assertSame(
            'https://example.com/jobs?role=pleno&utm_source=github&utm_medium=readme',
            $builder->build($link)
        );
    }

    public function test_detects_device_type_and_extracts_referer_host(): void
    {
        $builder = new UrlDestinationBuilder;

        $this->assertSame('mobile', $builder->detectDeviceType('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0)'));
        $this->assertSame('bot', $builder->detectDeviceType('Googlebot/2.1 (+http://www.google.com/bot.html)'));
        $this->assertSame('desktop', $builder->detectDeviceType('Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0)'));
        $this->assertSame('lnkd.in', $builder->extractRefererHost('https://LNKD.in/e/12345'));
        $this->assertSame('www.linkedin.com', $builder->extractRefererHost('https://www.linkedin.com/feed/'));
    }
}
