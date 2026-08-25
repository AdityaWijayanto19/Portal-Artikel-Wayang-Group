<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ArticleSeoMeta;
use App\Models\ArticleSitePublication;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use App\Models\WPSite;
use App\Services\WordPressPublisherService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublishPoolVerifyTest extends TestCase
{
    public function test_publish_uses_parallel_pool_and_returns_success(): void
    {
        Cache::flush();

        Storage::disk('public')->put('uploads/articles/fake-cover.webp', 'fake-image-bytes');

        Http::fake([
            '*/wp-json/wp/v2/users*' => Http::response([
                ['id' => 7, 'username' => 'john', 'slug' => 'john', 'email' => 'john@example.com'],
            ]),
            '*/wp-json/wp/v2/categories*' => Http::response([
                ['id' => 10, 'name' => 'News'],
            ]),
            '*/wp-json/wp/v2/tags*' => Http::response([
                ['id' => 20, 'name' => 'Tech'],
            ]),
            '*/wp-json/wp/v2/media' => Http::response(['id' => 99]),
            '*/wp-json/wp/v2/posts*' => function ($request) {
                $meta = data_get($request, 'meta', []);

                return Http::response([
                    'id' => 123,
                    'link' => 'https://wp.example.net/?p=123',
                    'meta' => $meta,
                ]);
            },
        ]);

        $site = new WPSite([
            'site_name' => 'Internal Site',
            'site_url' => 'https://wp.example.net',
            'wp_username' => 'admin',
            'wp_app_password' => 'abcd wxyz 1234 5678',
        ]);
        $site->id = 55;

        $author = new User(['id' => 1, 'username' => 'John', 'email' => 'john@example.com', 'name' => 'John']);

        $article = (new Article([
            'id' => 999,
            'title' => 'Judul Artikel',
            'slug' => 'judul-artikel',
            'content' => '<p>Konten artikel.</p>',
            'featured_image_path' => 'uploads/articles/fake-cover.webp',
            'status' => 'queued',
        ]))->setRelation('seoMeta', new ArticleSeoMeta([
            'yoast_title' => 'SEO Title',
            'yoast_metadesc' => 'Deskripsi.',
            'yoast_focuskw' => 'keyword',
            'seo_score' => 90,
            'readability_score' => 80,
            'reading_time_minutes' => 5,
        ]))
            ->setRelation('categories', new Collection([
                new Category(['id' => 1, 'name' => 'News']),
            ]))
            ->setRelation('tags', new Collection([
                new Tag(['id' => 2, 'name' => 'Tech']),
            ]))
            ->setRelation('author', $author)
            ->setRelation('sitePublications', new Collection([
                new ArticleSitePublication([
                    'article_id' => 999,
                    'wp_site_id' => $site->id,
                    'wp_post_id' => null,
                    'status' => 'queued',
                ]),
            ]));

        $result = $this->app->make(WordPressPublisherService::class)->publish($article, $site);

        $this->assertTrue($result['success']);
        $this->assertSame(123, $result['wp_post_id']);
        $this->assertSame(99, $result['wp_media_id']);
        $this->assertSame(7, $result['author_id']);
        $this->assertSame([10], $result['payload']['categories']);
        $this->assertSame([20], $result['payload']['tags']);

        Http::recorded()->each(function (array $pair): void {
            $this->assertNotInstanceOf(\Throwable::class, $pair[0]);
        });

        $this->assertSame(7, Cache::get('wp_author_id_site_'.$site->id.'_john_john@example.com'));
        $this->assertSame(10, Cache::get('wp_term_id_site_'.$site->id.'_categories_news'));
        $this->assertSame(20, Cache::get('wp_term_id_site_'.$site->id.'_tags_tech'));
    }

    public function test_publish_reuses_cached_terms_so_no_term_requests_are_made(): void
    {
        Cache::flush();
        Cache::put('wp_author_id_site_55_john_john@example.com', 7, now()->addDays(7));
        Cache::put('wp_term_id_site_55_categories_news', 10, now()->addDays(7));
        Cache::put('wp_term_id_site_55_tags_tech', 20, now()->addDays(7));

        Http::fake([
            '*/wp-json/wp/v2/media' => Http::response(['id' => 99]),
            '*/wp-json/wp/v2/posts*' => function ($request) {
                return Http::response([
                    'id' => 123,
                    'link' => 'https://wp.example.net/?p=123',
                    'meta' => data_get($request, 'meta', []),
                ]);
            },
        ]);

        $site = new WPSite([
            'site_name' => 'Internal Site',
            'site_url' => 'https://wp.example.net',
            'wp_username' => 'admin',
            'wp_app_password' => 'abcd wxyz 1234 5678',
        ]);
        $site->id = 55;

        $author = new User(['id' => 1, 'username' => 'John', 'email' => 'john@example.com', 'name' => 'John']);

        $article = (new Article([
            'id' => 999,
            'title' => 'Judul',
            'slug' => 'judul',
            'content' => '<p>Konten.</p>',
            'featured_image_path' => null,
        ]))->setRelation('seoMeta', new ArticleSeoMeta([
            'yoast_title' => 'T',
            'yoast_metadesc' => 'D',
            'yoast_focuskw' => 'k',
            'seo_score' => 90,
        ]))
            ->setRelation('categories', new Collection([
                new Category(['id' => 1, 'name' => 'News']),
            ]))
            ->setRelation('tags', new Collection([
                new Tag(['id' => 2, 'name' => 'Tech']),
            ]))
            ->setRelation('author', $author)
            ->setRelation('sitePublications', new Collection([
                new ArticleSitePublication(['article_id' => 999, 'wp_site_id' => 55, 'wp_post_id' => null, 'status' => 'queued']),
            ]));

        $this->app->make(WordPressPublisherService::class)->publish($article, $site);

        $urls = collect(Http::recorded())->map(fn ($pair) => $pair[0]->url())->all();

        $this->assertDoesNotMatchRegularExpression('/\/users\?/', implode(' ', $urls));
        $this->assertDoesNotMatchRegularExpression('/\/categories\?/', implode(' ', $urls));
        $this->assertDoesNotMatchRegularExpression('/\/tags\?/', implode(' ', $urls));
        $this->assertCount(2, Http::recorded());
    }
}
