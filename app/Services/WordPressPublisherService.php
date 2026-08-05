<?php

namespace App\Services;

use App\Models\Article;
use App\Models\WPSite;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WordPressPublisherService
{
    private const IMAGE_DISK = 'public';

    public function __construct(
        private readonly SeoAnalyzerService $seoAnalyzer,
        private readonly WpAuthorResolverService $authorResolver,
    ) {
    }

    /**
     * Publikasikan artikel ke SATU situs WordPress target.
     *
     * @return array{success: bool, wp_post_id: int, wp_media_id: int|null, author_id: int, message: string, payload: array<string, mixed>}
     */
    public function publish(Article $article, WPSite $site): array
    {
        $analysis = $this->seoAnalyzer->analyze($this->seoInput($article));
        $authorId = $this->authorResolver->resolveAuthorId($site, (string) $article->author?->username);

        // Mode simulasi untuk target lokal/demo agar alur queue bisa diuji tanpa WP nyata.
        if ($this->shouldSimulate($site)) {
            $mediaId = $article->featured_image_path ? random_int(100, 999) : null;

            return [
                'success' => true,
                'wp_post_id' => random_int(1000, 9999),
                'wp_media_id' => $mediaId,
                'author_id' => $authorId,
                'message' => 'Simulated WordPress publish for local/demo target ('.$site->site_name.').',
                'payload' => $this->buildPayload($article, $analysis, $mediaId, $authorId, [], []),
            ];
        }

        $client = $this->client($site);

        $mediaId = $this->uploadFeaturedImage($client, $article);
        $categoryIds = $this->resolveTerms($client, 'categories', $article->categories->pluck('name')->all());
        $tagIds = $this->resolveTerms($client, 'tags', $article->tags->pluck('name')->all());

        $payload = $this->buildPayload($article, $analysis, $mediaId, $authorId, $categoryIds, $tagIds);

        $response = $client->post('/posts', $payload);
        $response->throw();

        return [
            'success' => true,
            'wp_post_id' => (int) ($response->json('id') ?? 0),
            'wp_media_id' => $mediaId,
            'author_id' => $authorId,
            'message' => 'WordPress article published successfully to '.$site->site_name.'.',
            'payload' => $payload,
        ];
    }

    private function client(WPSite $site): PendingRequest
    {
        return Http::baseUrl(rtrim($site->site_url, '/').'/wp-json/wp/v2')
            ->withBasicAuth($site->wp_username, $site->wp_app_password)
            ->acceptJson()
            ->timeout(30);
    }

    /**
     * Unggah featured image ke /wp/v2/media milik situs ini dan kembalikan media_id.
     */
    private function uploadFeaturedImage(PendingRequest $client, Article $article): ?int
    {
        if (! $article->featured_image_path) {
            return null;
        }

        $disk = Storage::disk(self::IMAGE_DISK);

        if (! $disk->exists($article->featured_image_path)) {
            return null;
        }

        $response = $client
            ->attach('file', $disk->get($article->featured_image_path), basename($article->featured_image_path))
            ->post('/media', [
                'alt_text' => $article->image_alt_text,
            ]);

        $response->throw();

        return (int) ($response->json('id') ?? 0);
    }

    /**
     * Petakan nama term (kategori/tag) ke ID pada situs target: cari dulu, buat bila belum ada.
     *
     * @param  array<int, string>  $names
     * @return array<int, int>
     */
    private function resolveTerms(PendingRequest $client, string $taxonomy, array $names): array
    {
        $ids = [];

        foreach (array_filter($names) as $name) {
            try {
                $search = $client->get("/{$taxonomy}", ['search' => $name, 'per_page' => 1]);
                $existing = collect($search->json())->firstWhere('name', $name)['id'] ?? null;

                if ($existing) {
                    $ids[] = (int) $existing;

                    continue;
                }

                $created = $client->post("/{$taxonomy}", ['name' => $name]);

                if ($created->successful()) {
                    $ids[] = (int) $created->json('id');
                }
            } catch (\Throwable) {
                // Abaikan kegagalan satu term agar tidak menggagalkan seluruh publikasi.
                continue;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  array<string, mixed>  $analysis
     * @param  array<int, int>  $categoryIds
     * @param  array<int, int>  $tagIds
     * @return array<string, mixed>
     */
    private function buildPayload(
        Article $article,
        array $analysis,
        ?int $mediaId,
        ?int $authorId,
        array $categoryIds,
        array $tagIds,
    ): array {
        return [
            'title' => $article->title,
            'content' => $article->content,
            'slug' => $article->slug,
            'status' => 'publish',
            'author' => $authorId,
            'featured_media' => $mediaId,
            'categories' => $categoryIds,
            'tags' => $tagIds,
            'meta' => [
                '_yoast_wpseo_linkdex' => (string) $analysis['score'],
                '_yoast_wpseo_content_score' => (string) $analysis['content_score'],
                '_yoast_wpseo_estimated_reading_time_minutes' => (string) $analysis['estimated_reading_time_minutes'],
                '_yoast_wpseo_title' => $article->seoMeta?->yoast_title ?? $article->yoast_title ?? $article->title,
                '_yoast_wpseo_metadesc' => $article->seoMeta?->yoast_metadesc ?? $article->yoast_metadesc,
                '_yoast_wpseo_focuskw' => $article->seoMeta?->yoast_focuskw ?? $article->yoast_focuskw,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function seoInput(Article $article): array
    {
        $seo = $article->seoMeta;

        return [
            'title' => $article->title,
            'slug' => $article->slug,
            'content' => $article->content,
            'image_alt_text' => $article->image_alt_text,
            'yoast_title' => $seo?->yoast_title ?? $article->yoast_title,
            'yoast_metadesc' => $seo?->yoast_metadesc ?? $article->yoast_metadesc,
            'yoast_focuskw' => $seo?->yoast_focuskw ?? $article->yoast_focuskw,
        ];
    }

    private function shouldSimulate(WPSite $site): bool
    {
        return Str::contains($site->site_url, ['.local', 'example.com', 'localhost']);
    }
}
