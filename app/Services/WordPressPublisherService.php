<?php

namespace App\Services;

use App\Models\Article;
use App\Models\User;
use App\Models\WPSite;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WordPressPublisherService
{
    private const IMAGE_DISK = 'public';

    private const USER_AGENT = 'Mozilla/5.0 WayangGroup/1.0';

    private const UPLOAD_MAX_RETRIES = 3;

    public function __construct(
        private readonly SeoAnalyzerService $seoAnalyzer,
        private readonly WpAuthorResolverService $authorResolver,
    ) {}

    /**
     * Publikasikan (create) ATAU perbarui (update) artikel pada SATU situs WordPress target.
     *
     * Update otomatis terjadi bila situs tersebut sudah memiliki wp_post_id pada
     * mapping article_site_publications — alih-alih membuat post baru (duplikat).
     *
     * Alur:
     *   1. Kirim payload artikel utama (tanpa `meta`) → dapatkan wp_post_id.
     *   2. Kirim request TERPISAH khusus metadata Yoast SEO. Kegagalan request ini
     *      tidak membatalkan keberhasilan publikasi artikel utama (hanya dicatat log).
     *
     * @return array{success: bool, wp_post_id: int|null, wp_media_id: int|null, author_id: int|null, published_url: string|null, message: string, payload: array<string, mixed>, meta_warning: string|null}
     */
    public function publish(Article $article, WPSite $site): array
    {
        Log::info('[WPPublish] START', [
            'article_id' => $article->id,
            'article_title' => $article->title,
            'site' => $site->site_name,
            'site_url' => $site->site_url,
        ]);

        $article->loadMissing(['seoMeta', 'categories', 'tags', 'author', 'sitePublications']);
        $analysis = $this->seoAnalyzer->analyze($this->seoInput($article));
        $authorId = $this->resolveWordPressAuthorId($site, $article->author);

        // Mapping wp_post_id pada situs target — penentu alur create vs update (Task 3).
        $existingPostId = $article->sitePublications
            ->firstWhere('wp_site_id', $site->id)?->wp_post_id;

        Log::info('[WPPublish] Resolved', [
            'article_id' => $article->id,
            'author_id' => $authorId,
            'existing_post_id' => $existingPostId,
            'simulate' => $this->shouldSimulate($site),
        ]);

        // Mode simulasi untuk target lokal/demo agar alur queue bisa diuji tanpa WP nyata.
        if ($this->shouldSimulate($site)) {
            $mediaId = $article->featured_image_path ? random_int(100, 999) : null;

            return [
                'success' => true,
                'wp_post_id' => $existingPostId ?? random_int(1000, 9999),
                'wp_media_id' => $mediaId,
                'author_id' => $authorId,
                'published_url' => null,
                'message' => 'Simulated WordPress publish for local/demo target ('.$site->site_name.').',
                'payload' => $this->buildPayload($article, $mediaId, $authorId, [], []),
                'meta_warning' => null,
            ];
        }

        $client = $this->client($site);

        try {
            return $this->publishToLive($client, $article, $site, $authorId, $existingPostId, $analysis);
        } catch (\Throwable $e) {
            Log::error('[WPPublish] FAILED', [
                'article_id' => $article->id,
                'site' => $site->site_name,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            throw $e;
        }
    }

    private function publishToLive(
        PendingRequest $client,
        Article $article,
        WPSite $site,
        ?int $authorId,
        ?int $existingPostId,
        array $analysis,
    ): array {
        Log::info('[WPPublish] Uploading featured image', ['article_id' => $article->id, 'path' => $article->featured_image_path]);
        $mediaId = $this->uploadFeaturedImage($site, $article);
        Log::info('[WPPublish] Featured image done', ['article_id' => $article->id, 'media_id' => $mediaId]);

        // Client baru yang BERSIH untuk fase term & artikel. Jangan pernah memakai
        // objek PendingRequest yang sudah melalui withBody()/attach() — state-nya
        // persisten (bodyFormat, Content-Type, Content-Disposition) dan merusak
        // request berikutnya (mis. POST /posts jadi body kosong → 403 WAF).
        $client = $this->client($site);

        $categoryIds = $this->resolveTerms($client, 'categories', $article->categories->pluck('name')->all());
        $tagIds = $this->resolveTerms($client, 'tags', $article->tags->pluck('name')->all());

        $payload = $this->buildPayload($article, $mediaId, $authorId, $categoryIds, $tagIds);

        Log::info('[WPPublish] Submitting article payload', [
            'article_id' => $article->id,
            'mode' => $existingPostId ? 'update' : 'create',
            'endpoint' => $existingPostId ? "/posts/{$existingPostId}" : '/posts',
            'payload_keys' => array_keys($payload),
        ]);

        // Task 3: update post yang sudah ada (PUT/POST ke /posts/{id}) vs buat baru.
        // Timeout artikel mengikuti referensi Python: 60 detik.
        $response = $existingPostId
            ? $client->timeout(60)->post("/posts/{$existingPostId}", $payload)
            : $client->timeout(60)->post('/posts', $payload);

        Log::info('[WPPublish] Article response', [
            'article_id' => $article->id,
            'http_status' => $response->status(),
            'body_preview' => mb_substr($response->body(), 0, 500),
        ]);

        $response->throw();

        $wpPostId = (int) ($response->json('id') ?? $existingPostId);
        $publishedUrl = (string) ($response->json('link') ?? rtrim($site->site_url, '/').'/?p='.$wpPostId);

        // Task 1: metadata Yoast dikirim terpisah — kegagalan tidak membatalkan publish.
        $metaWarning = $this->updateYoastMeta($client, $wpPostId, $this->yoastMeta($article, $analysis));

        $message = $existingPostId
            ? 'WordPress article updated successfully on '.$site->site_name.'.'
            : 'WordPress article published successfully to '.$site->site_name.'.';

        Log::info('[WPPublish] DONE', [
            'article_id' => $article->id,
            'wp_post_id' => $wpPostId,
            'published_url' => $publishedUrl,
            'meta_warning' => $metaWarning,
        ]);

        return [
            'success' => true,
            'wp_post_id' => $wpPostId,
            'wp_media_id' => $mediaId,
            'author_id' => $authorId,
            'published_url' => $publishedUrl,
            'message' => $metaWarning ? $message.' '.$metaWarning : $message,
            'payload' => $payload,
            'meta_warning' => $metaWarning,
        ];
    }

    private function client(WPSite $site): PendingRequest
    {
        $client = Http::baseUrl(rtrim($site->site_url, '/').'/wp-json/wp/v2')
            ->withBasicAuth($site->wp_username, $site->appPassword())
            ->acceptJson()
            ->withHeaders(['User-Agent' => self::USER_AGENT])
            ->timeout(30);

        return $client;
    }

    /**
     * Unggah featured image ke /wp/v2/media milik situs ini dan kembalikan media_id.
     *
     * Meniru strategi Python teman user SECARA PERSIS:
     *   - RAW BODY (bukan multipart) + header `Content-Type` & `Content-Disposition`
     *     `attachment; filename="..."`. Multipart kena blokir WAF (403 HTML) di
     *     beberapa situs (Wordfence/ModSecurity/Cloudflare), raw body tidak.
     *   - Beberapa percobaan dengan exponential backoff (2s, 4s, 8s).
     *   - Timeout panjang (120s) karena media mentah bisa berukuran besar.
     *
     * Kegagalan total mengembalikan null — artikel tetap dipublikasikan tanpa
     * gambar unggulan (tidak mematikan alur publish).
     */
    private function uploadFeaturedImage(WPSite $site, Article $article): ?int
    {
        if (! $article->featured_image_path) {
            return null;
        }

        $disk = Storage::disk(self::IMAGE_DISK);

        if (! $disk->exists($article->featured_image_path)) {
            return null;
        }

        $filename = basename($article->featured_image_path);

        for ($attempt = 1; $attempt <= self::UPLOAD_MAX_RETRIES; $attempt++) {
            try {
                // Client FRESH per percobaan: PendingRequest bersifat mutable &
                // persisten, jadi memakai objek bersama akan menodai state
                // (bodyFormat='body', Content-Type, Content-Disposition) untuk
                // request lain. Client baru menjamin request artikel tetap bersih.
                $response = $this->client($site)
                    ->timeout(120)
                    ->withBody($disk->get($article->featured_image_path), $this->mimeTypeFor($filename))
                    ->withHeaders(['Content-Disposition' => 'attachment; filename="'.$filename.'"'])
                    ->post('/media');

                $response->throw();

                Log::info('[WPPublish] Image upload ok', [
                    'article_id' => $article->id,
                    'media_id' => $response->json('id'),
                    'http_status' => $response->status(),
                ]);

                return (int) ($response->json('id') ?? 0);
            } catch (\Throwable $e) {
                $httpStatus = isset($e->response) ? $e->response->status() : null;
                $bodyPreview = isset($e->response) ? mb_substr($e->response->body(), 0, 500) : null;
                $bodyTail = isset($e->response) ? mb_substr($e->response->body(), -500) : null;
                $responseHeaders = isset($e->response) ? $e->response->headers() : [];

                $wafHints = array_intersect_key($responseHeaders, array_flip([
                    'server', 'via', 'x-powered-by', 'x-sucuri-id', 'x-sucuri-cache',
                    'cf-ray', 'x-cdn', 'x-wf', 'x-wf-wall', 'x-litespeed-cache',
                    'x-cache', 'x-cache-hits', 'x-qm', 'x-hostinger', 'x-cloudee',
                    'x-cdn-rule-id', 'x-kinsta-cache', 'x-nginx-proxy', 'x-hw',
                    'set-cookie',
                ]));

                Log::warning('Upload gambar WP gagal', [
                    'article_id' => $article->id,
                    'attempt' => $attempt,
                    'max_retries' => self::UPLOAD_MAX_RETRIES,
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'http_status' => $httpStatus,
                    'body_head' => $bodyPreview,
                    'body_tail' => $bodyTail,
                    'response_headers' => $wafHints,
                ]);

                if ($attempt === self::UPLOAD_MAX_RETRIES) {
                    return null;
                }

                sleep(2 ** $attempt); // backoff: 2s, 4s, 8s (2^1, 2^2, 2^3)
            }
        }

        return null;
    }

    /**
     * Deteksi MIME type dari ekstensi file — padanan `mime_type_by_extension()`
     * di kode Python teman user.
     */
    private function mimeTypeFor(string $filename): string
    {
        return match (strtolower(pathinfo($filename, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'avif' => 'image/avif',
            'svg' => 'image/svg+xml',
            'bmp' => 'image/bmp',
            'ico' => 'image/x-icon',
            'tiff', 'tif' => 'image/tiff',
            default => 'application/octet-stream',
        };
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

                    Log::info('[WPPublish] Term found', ['taxonomy' => $taxonomy, 'name' => $name, 'id' => $existing]);

                    continue;
                }

                $created = $client->post("/{$taxonomy}", ['name' => $name]);

                if ($created->successful()) {
                    $ids[] = (int) $created->json('id');

                    Log::info('[WPPublish] Term created', ['taxonomy' => $taxonomy, 'name' => $name, 'id' => $created->json('id')]);
                }
            } catch (\Throwable $e) {
                Log::warning('[WPPublish] Term resolve failed', [
                    'taxonomy' => $taxonomy,
                    'name' => $name,
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                ]);

                // Abaikan kegagalan satu term agar tidak menggagalkan seluruh publikasi.
                continue;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Rakit payload artikel utama. TIDAK menyertakan `meta` — metadata Yoast
     * dikirim lewat request terpisah agar kegagalan meta tidak menggagalkan
     * seluruh publikasi artikel.
     *
     * @param  array<int, int>  $categoryIds
     * @param  array<int, int>  $tagIds
     * @return array<string, mixed>
     */
    private function buildPayload(
        Article $article,
        ?int $mediaId,
        ?int $authorId,
        array $categoryIds,
        array $tagIds,
    ): array {
        $payload = [
            'title' => $article->title,
            'content' => $article->content,
            'slug' => $article->slug,
            'status' => 'publish',
            'featured_media' => $mediaId,
            'categories' => $categoryIds,
            'tags' => $tagIds,
        ];

        // Task 2: bila author tidak ditemukan di WordPress, jangan sertakan atribut
        // 'author' → WP otomatis fallback ke akun default (admin) tanpa membatalkan publish.
        if ($authorId !== null) {
            $payload['author'] = $authorId;
        }

        return $payload;
    }

    /**
     * Task 1: perbarui metadata Yoast SEO lewat request TERPISAH ke
     * /wp/v2/posts/{post_id}. Kegagalan HTTP ditangkap & dicatat sebagai warning
     * tanpa membatalkan status sukses artikel utama.
     *
     * Selain itu, deteksi kunci yang hilang pada respons (silent drop): WordPress
     * hanya menyimpan meta yang diregistrasi dengan show_in_rest => true.
     *
     * @param  array<string, string>  $meta
     */
    private function updateYoastMeta(PendingRequest $client, int $postId, array $meta): ?string
    {
        try {
            // Timeout meta mengikuti referensi Python: 30 detik.
            $response = $client->timeout(30)->post("/posts/{$postId}", ['meta' => $meta]);
            $response->throw();

            $savedMeta = $response->json('meta') ?? [];

            $missing = collect($meta)
                ->filter(fn (string $value) => $value !== '')
                ->keys()
                ->filter(fn (string $key) => ! array_key_exists($key, $savedMeta) || $savedMeta[$key] === '')
                ->values();

            if ($missing->isNotEmpty()) {
                Log::warning("Meta Yoast tidak tersimpan untuk WP post #{$postId}: ".$missing->implode(', '));

                return 'Meta Yoast tidak tersimpan di WordPress: '.$missing->implode(', ')
                    .'. Daftarkan kunci meta di situs tujuan dengan `register_post_meta(... show_in_rest => true)`.';
            }

            return null;
        } catch (\Throwable $e) {
            Log::warning("Gagal menyinkronkan meta Yoast ke WP post #{$postId}: ".$e->getMessage());

            return 'Meta Yoast gagal disinkronkan (artikel utama tetap berhasil). '.$e->getMessage();
        }
    }

    /**
     * Task 2: resolusi WP User ID untuk author artikel.
     * Meneruskan ke WpAuthorResolverService yang melakukan exact match username/slug/email;
     * mengembalikan null bila tidak ditemukan atau request error (fallback author default WP).
     */
    public function resolveWordPressAuthorId(WPSite $site, ?User $author): ?int
    {
        if ($author === null || trim((string) $author->username) === '') {
            return null;
        }

        return $this->authorResolver->resolveAuthorId(
            $site,
            (string) $author->username,
            (string) ($author->email ?? ''),
        );
    }

    /**
     * Task 3: hapus post dari WordPress (force delete, tidak hanya pindah ke trash).
     */
    public function deleteFromWordPress(WPSite $site, int $wpPostId): bool
    {
        $response = $this->client($site)->delete("/posts/{$wpPostId}", ['force' => true]);
        $response->throw();

        return $response->successful();
    }

    /**
     * Rakit metadata Yoast SEO untuk dikirim lewat request `meta` terpisah.
     *
     * Sumber data: relasi ArticleSeoMeta (sumber kebenaran) dengan fallback ke
     * kolom legacy articles.yoast_*. Selalu dikoersikan ke string agar sesuai
     * dengan schema `type: string` yang didaftarkan Yoast.
     *
     * @param  array<string, mixed>  $analysis
     * @return array<string, string>
     */
    private function yoastMeta(Article $article, array $analysis): array
    {
        $seo = $article->seoMeta;

        return [
            '_yoast_wpseo_title' => (string) ($seo?->yoast_title ?? $article->yoast_title ?? $article->title),
            '_yoast_wpseo_metadesc' => (string) ($seo?->yoast_metadesc ?? $article->yoast_metadesc ?? ''),
            '_yoast_wpseo_focuskw' => (string) ($seo?->yoast_focuskw ?? $article->yoast_focuskw ?? ''),
            '_yoast_wpseo_linkdex' => (string) ($analysis['score'] ?? 0),
            '_yoast_wpseo_content_score' => (string) ($analysis['content_score'] ?? 0),
            '_yoast_wpseo_estimated_reading_time_minutes' => (string) ($analysis['estimated_reading_time_minutes'] ?? 0),
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
