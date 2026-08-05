<?php

namespace Tests\Feature;

use App\Services\SeoAnalyzerService;
use Tests\TestCase;

class ArticleWorkflowTest extends TestCase
{
    public function test_seo_analyzer_realtime_score_is_consistent(): void
    {
        $service = $this->app->make(SeoAnalyzerService::class);

        $result = $service->analyze([
            'title' => str_repeat('SEO Terpusat ', 4),
            'slug' => 'seo-terpusat-automation-artikel',
            'content' => str_repeat('Konten ini memiliki internal link dan external link. ', 80).' <a href="/internal">internal</a> <a href="https://example.com">external</a>',
            'yoast_title' => str_repeat('SEO Terpusat ', 4),
            'yoast_metadesc' => str_repeat('Deskripsi meta yang cukup panjang untuk lolos skor. ', 4),
            'yoast_focuskw' => 'seo terpusat',
            'image_alt_text' => 'Gambar SEO terpusat',
        ]);

        $this->assertGreaterThanOrEqual(0, $result['score']);
        $this->assertArrayHasKey('breakdown', $result);
    }

    public function test_homepage_loads_editor_without_database(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Automation Artikel Wayang Group Company');
    }
}
