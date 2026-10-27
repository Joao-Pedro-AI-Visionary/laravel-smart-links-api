<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmartLinkApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_short_link_with_custom_or_generated_slug_and_validates_input(): void
    {
        // Invalid URL rejected with 422 (CA-01)
        $this->postJson('/api/v1/links', [
            'title' => 'Meu GitHub',
            'target_url' => 'not-a-valid-url',
        ])->assertStatus(422);

        // SSRF protection: private/metadata IP in webhook_url rejected with 422
        $this->postJson('/api/v1/links', [
            'title' => 'SSRF Attempt',
            'target_url' => 'https://example.com',
            'webhook_url' => 'http://169.254.169.254/latest/meta-data/',
        ])->assertStatus(422);

        // Valid custom slug created with 201 (CA-01)
        $response = $this->postJson('/api/v1/links', [
            'title' => 'Portfólio Vue & Laravel',
            'slug' => 'meu-portfolio',
            'target_url' => 'https://github.com/Joao-Pedro-AI-Visionary',
            'utm_source' => 'linkedin',
            'utm_medium' => 'recruiter_dm',
            'webhook_url' => 'https://hooks.example.com/click-alert',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.slug', 'meu-portfolio')
            ->assertJsonPath('data.utm_source', 'linkedin');

        // Duplicate slug rejected with 422
        $this->postJson('/api/v1/links', [
            'title' => 'Duplicado',
            'slug' => 'meu-portfolio',
            'target_url' => 'https://example.com',
        ])->assertStatus(422);

        // Omitted slug auto-generates 7-char slug
        $autoResponse = $this->postJson('/api/v1/links', [
            'title' => 'Auto Slug Link',
            'target_url' => 'https://example.com/docs',
        ]);

        $autoResponse->assertStatus(201);
        $this->assertNotEmpty($autoResponse->json('data.slug'));

        // Paginated list endpoint returns created links
        $this->getJson('/api/v1/links')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_redirects_with_utm_parameters_records_telemetry_and_fires_webhook(): void
    {
        Http::fake();

        $this->postJson('/api/v1/links', [
            'title' => 'Currículo Pleno',
            'slug' => 'cv-pleno',
            'target_url' => 'https://example.com/curriculo?lang=pt',
            'utm_source' => 'curriculo_pdf',
            'utm_medium' => 'cta_header',
            'webhook_url' => 'https://hooks.example.com/notify',
        ])->assertStatus(201);

        // First click from Desktop
        $redirect = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0) AppleWebKit/537.36',
            'Referer' => 'https://www.linkedin.com/in/recruiter/',
        ])->get('/r/cv-pleno');

        $redirect->assertStatus(302)
            ->assertRedirect('https://example.com/curriculo?lang=pt&utm_source=curriculo_pdf&utm_medium=cta_header');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://hooks.example.com/notify'
                && $request['slug'] === 'cv-pleno'
                && $request['device_type'] === 'desktop';
        });

        // Second click from Mobile
        $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) Mobile/15E148',
            'Referer' => 'https://github.com/Joao-Pedro-AI-Visionary',
        ])->get('/r/cv-pleno')->assertStatus(302);

        // Query aggregated telemetry (CA-04)
        $stats = $this->getJson('/api/v1/links/cv-pleno/stats');
        $stats->assertStatus(200)
            ->assertJsonPath('data.slug', 'cv-pleno')
            ->assertJsonPath('data.total_clicks', 2)
            ->assertJsonPath('data.unique_visitors', 2)
            ->assertJsonPath('data.by_device.desktop', 1)
            ->assertJsonPath('data.by_device.mobile', 1);
    }

    public function test_returns_410_gone_when_short_link_is_expired_and_supports_deletion(): void
    {
        $this->postJson('/api/v1/links', [
            'title' => 'Link Expirado',
            'slug' => 'vaga-antiga',
            'target_url' => 'https://example.com/expired',
            'expires_at' => now()->subDay()->toIso8601String(),
        ])->assertStatus(201);

        $this->get('/r/vaga-antiga')->assertStatus(410);

        // Update link via PUT /api/v1/links/{slug}
        $this->putJson('/api/v1/links/vaga-antiga', [
            'title' => 'Link Renovado Pleno',
            'target_url' => 'https://example.com/renewed',
            'utm_source' => 'github_profile',
        ])->assertStatus(200)
            ->assertJsonPath('data.title', 'Link Renovado Pleno')
            ->assertJsonPath('data.target_url', 'https://example.com/renewed');

        $this->deleteJson('/api/v1/links/vaga-antiga')->assertStatus(204);
        $this->getJson('/api/v1/links/vaga-antiga/stats')->assertStatus(404);
    }
}
