<?php

namespace App\Services;

use App\Models\Article;
use App\Models\User;
use App\Models\WPSite;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WordPressPublisherService
{
    private const IMAGE_DISK = 'public';

    private const USER_AGENT = 'Mozilla/5.0 WayangGroup/1.0';

    public function __construct(
        private readonly SeoAnalyzerService $seoAnalyzer,
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

        // Mapping wp_post_id pada situs target — penentu alur create vs update (Task 3).
        $existingPostId = $article->sitePublications
            ->firstWhere('wp_site_id', $site->id)?->wp_post_id;

        Log::info('[WPPublish] Resolved', [
            'article_id' => $article->id,
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
                'author_id' => null,
                'published_url' => null,
                'message' => 'Simulated WordPress publish for local/demo target ('.$site->site_name.').',
                'payload' => $this->buildPayload($article, $mediaId, null, [], []),
                'meta_warning' => null,
            ];
        }

        try {
            return $this->publishToLive($article, $site, $existingPostId, $analysis);
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
        Article $article,
        WPSite $site,
        ?int $existingPostId,
        array $analysis,
    ): array {
        $waveStart = microtime(true);

        Log::info('[WPPublish] Building parallel first wave', [
            'article_id' => $article->id,
            'path' => $article->featured_image_path,
            'categories' => $article->categories->pluck('name')->all(),
            'tags' => $article->tags->pluck('name')->all(),
        ]);

        $authorSearch = $this->authorSearchDescriptor($site, $article->author);
        $categoryLookups = $this->termLookupPlan($site, 'categories', $article->categories->pluck('name')->all());
        $tagLookups = $this->termLookupPlan($site, 'tags', $article->tags->pluck('name')->all());
        $imagePlan = $this->imageUploadPlan($article);

        // GELOMBANG PARALEL: resolusi author, upload gambar, dan pencarian term
        // kategori/tag berjalan BERSAMAAN (Http::pool → satu CurlMultiHandler,
        // koneksi di-reuse + 1 TLS handshake). Request serial 8–12 → 1 wave.
        $responses = $this->runFirstWave($site, $authorSearch, $categoryLookups, $tagLookups, $imagePlan);

        $authorId = $this->resolveAuthorFromWave($site, $authorSearch, $responses);
        $mediaId = $this->resolveImageFromWave($site, $article, $imagePlan, $responses);

        [$categoryIds, $categoriesToCreate] = $this->resolveTermsFromWave($site, 'categories', $categoryLookups, $responses);
        [$tagIds, $tagsToCreate] = $this->resolveTermsFromWave($site, 'tags', $tagLookups, $responses);

        Log::info('[WPPublish] First wave resolved', [
            'article_id' => $article->id,
            'author_id' => $authorId,
            'media_id' => $mediaId,
            'category_ids' => $categoryIds,
            'tag_ids' => $tagIds,
            'categories_to_create' => $categoriesToCreate,
            'tags_to_create' => $tagsToCreate,
            'wave_ms' => round((microtime(true) - $waveStart) * 1000, 2),
        ]);

        // Client baru yang BERSIH untuk fase term-create & artikel. Jangan pernah
        // memakai objek PendingRequest yang sudah melalui withBody()/attach() —
        // state-nya persisten (bodyFormat, Content-Type, Content-Disposition) dan
        // merusak request berikutnya (mis. POST /posts jadi body kosong → 403 WAF).
        $client = $this->client($site);

        foreach ($categoriesToCreate as $name) {
            $termId = $this->createTerm($client, 'categories', $name);

            if ($termId !== null) {
                $categoryIds[] = $termId;
                $this->cacheTermId($site->id, 'categories', $name, $termId);
            }
        }

        foreach ($tagsToCreate as $name) {
            $termId = $this->createTerm($client, 'tags', $name);

            if ($termId !== null) {
                $tagIds[] = $termId;
                $this->cacheTermId($site->id, 'tags', $name, $termId);
            }
        }

        $categoryIds = array_values(array_unique($categoryIds));
        $tagIds = array_values(array_unique($tagIds));

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
     * Deskriptor pencarian author WP. Hasil positif di-cache 7 hari (kunci sama
     * dengan WpAuthorResolverService) sehingga situs yang sama tidak perlu
     * melakukan lookup ulang.
     *
     * @return array{cache: bool, id: int|null, key: string, username: string, email: string}|null
     */
    private function authorSearchDescriptor(WPSite $site, ?User $author): ?array
    {
        if ($author === null) {
            return null;
        }

        $username = strtolower(trim((string) $author->username));
        $email = strtolower(trim((string) ($author->email ?? '')));

        if ($username === '') {
            return null;
        }

        $key = "wp_author_id_site_{$site->id}_{$username}_{$email}";
        $cached = Cache::get($key);

        return [
            'cache' => is_numeric($cached),
            'id' => is_numeric($cached) ? (int) $cached : null,
            'key' => $key,
            'username' => $username,
            'email' => $email,
        ];
    }

    /**
     * Rencana lookup term (kategori/tag): term yang sudah pernah ditemukan/
     * dibuat pada situs ini di-cache 7 hari sehingga publish berikutnya TIDAK
     * melakukan request ke WP sama sekali untuk term tersebut.
     *
     * @param  array<int, string>  $names
     * @return array<string, array{cache: bool, id: int|null, key: string}>
     */
    private function termLookupPlan(WPSite $site, string $taxonomy, array $names): array
    {
        $plan = [];

        foreach (array_filter(array_map('trim', $names)) as $name) {
            $key = "wp_term_id_site_{$site->id}_{$taxonomy}_".mb_strtolower($name);
            $cached = Cache::get($key);

            $plan[$name] = [
                'cache' => is_numeric($cached),
                'id' => is_numeric($cached) ? (int) $cached : null,
                'key' => $key,
            ];
        }

        return $plan;
    }

    /**
     * Baca berkas featured image siap kirim (RAW BODY, bukan multipart). Meniru
     * strategi Python: header `Content-Type` & `Content-Disposition` `attachment;
     * filename="..."` — multipart kena blokir WAF (403 HTML) di beberapa situs
     * (Wordfence/ModSecurity/Cloudflare), raw body tidak.
     *
     * @return array{body: string, mime: string, filename: string}|null
     */
    private function imageUploadPlan(Article $article): ?array
    {
        if (! $article->featured_image_path) {
            return null;
        }

        $disk = Storage::disk(self::IMAGE_DISK);

        if (! $disk->exists($article->featured_image_path)) {
            return null;
        }

        $filename = basename($article->featured_image_path);

        return [
            'body' => $disk->get($article->featured_image_path),
            'mime' => $this->mimeTypeFor($filename),
            'filename' => $filename,
        ];
    }

    /**
     * GELOMBANG PARALEL pertama: resolusi author, upload gambar, dan pencarian
     * term kategori/tag dijalankan BERSAMAAN lewat satu Http::pool (berbagi satu
     * CurlMultiHandler → koneksi di-reuse + satu TLS handshake). Hasil kegagalan
     * satu request tidak menggagalkan yang lain (berupa Throwable di array hasil).
     *
     * @param  array{cache: bool, id: int|null, key: string, username: string, email: string}|null  $authorSearch
     * @param  array<string, array{cache: bool, id: int|null, key: string}>  $categoryLookups
     * @param  array<string, array{cache: bool, id: int|null, key: string}>  $tagLookups
     * @param  array{body: string, mime: string, filename: string}|null  $imagePlan
     * @return array<string, Response|\Throwable>
     */
    private function runFirstWave(
        WPSite $site,
        ?array $authorSearch,
        array $categoryLookups,
        array $tagLookups,
        ?array $imagePlan,
    ): array {
        return Http::pool(function (Pool $pool) use ($site, $authorSearch, $categoryLookups, $tagLookups, $imagePlan): array {
            $requests = [];

            if ($authorSearch && ! $authorSearch['cache']) {
                if ($authorSearch['username'] !== '') {
                    $requests[] = $this->poolRequest($pool, 'author_username', $site, 10)
                        ->get('/users', ['search' => $authorSearch['username'], 'per_page' => 20]);
                }

                if ($authorSearch['email'] !== '') {
                    $requests[] = $this->poolRequest($pool, 'author_email', $site, 10)
                        ->get('/users', ['search' => $authorSearch['email'], 'per_page' => 20]);
                }
            }

            if ($imagePlan) {
                $requests[] = $this->poolRequest($pool, 'image', $site, 120)
                    ->withBody($imagePlan['body'], $imagePlan['mime'])
                    ->withHeaders(['Content-Disposition' => 'attachment; filename="'.$imagePlan['filename'].'"'])
                    ->post('/media');
            }

            foreach ($categoryLookups as $name => $plan) {
                if (! $plan['cache']) {
                    $requests[] = $this->poolRequest($pool, 'categories:'.$name, $site, 15)
                        ->get('/categories', ['search' => $name, 'per_page' => 1]);
                }
            }

            foreach ($tagLookups as $name => $plan) {
                if (! $plan['cache']) {
                    $requests[] = $this->poolRequest($pool, 'tags:'.$name, $site, 15)
                        ->get('/tags', ['search' => $name, 'per_page' => 1]);
                }
            }

            return $requests;
        }, 20);
    }

    /**
     * PendingRequest async untuk pool — selalu fresh per key (pending request
     * bersifat mutable & persisten; memakai objek bersama menodai state body
     * untuk request lain).
     */
    private function poolRequest(Pool $pool, string $key, WPSite $site, int $timeout = 30): PendingRequest
    {
        return $pool->as($key)
            ->baseUrl(rtrim($site->site_url, '/').'/wp-json/wp/v2')
            ->withBasicAuth($site->wp_username, $site->appPassword())
            ->acceptJson()
            ->withHeaders(['User-Agent' => self::USER_AGENT])
            ->timeout($timeout);
    }

    /**
     * Resolusi author dari hasil wave paralel. Nilai positif (atau null bila
     * lookup menyatakan tidak ditemukan) di-cache 7 hari — atribut 'author'
     * di-omit pada payload bila null sehingga WP memakai akun default/admin.
     *
     * @param  array{cache: bool, id: int|null, key: string, username: string, email: string}|null  $authorSearch
     * @param  array<string, Response|\Throwable>  $responses
     */
    private function resolveAuthorFromWave(WPSite $site, ?array $authorSearch, array $responses): ?int
    {
        if ($authorSearch === null) {
            return null;
        }

        if ($authorSearch['cache']) {
            Log::info('[WPPublish] Author from cache', [
                'author_username' => $authorSearch['username'],
                'site' => $site->site_name,
                'author_id' => $authorSearch['id'],
            ]);

            return $authorSearch['id'];
        }

        $candidates = [];

        foreach (['author_username', 'author_email'] as $responseKey) {
            $response = $responses[$responseKey] ?? null;

            if ($response instanceof \Throwable || $response === null || ! $response->successful()) {
                continue;
            }

            $candidates = array_merge($candidates, $response->json() ?? []);
        }

        $authorId = $this->matchAuthorCandidate($authorSearch['username'], $authorSearch['email'], $candidates);

        if ($authorId !== null) {
            Cache::put($authorSearch['key'], $authorId, now()->addDays(7));

            Log::info("[WPPublish] Author resolved: '{$authorSearch['username']}' on '{$site->site_name}' => ID {$authorId}");
        } else {
            Log::info("WP Author '{$authorSearch['username']}' tidak ditemukan di '{$site->site_name}' — memakai author default WordPress.");
        }

        return $authorId;
    }

    /**
     * Exact match username/slug/email terhadap daftar user WP (padanan logika
     * WpAuthorResolverService).
     *
     * @param  array<int, array<string, mixed>>  $candidates
     */
    private function matchAuthorCandidate(string $username, string $email, array $candidates): ?int
    {
        foreach ($candidates as $user) {
            $matched = ($username !== '' && strtolower((string) ($user['username'] ?? '')) === $username)
                || ($username !== '' && strtolower((string) ($user['slug'] ?? '')) === $username)
                || ($email !== '' && strtolower((string) ($user['email'] ?? '')) === $email);

            if ($matched && isset($user['id'])) {
                return (int) $user['id'];
            }
        }

        return null;
    }

    /**
     * Ambil media_id dari hasil wave; bila gagal dengan status yang LAYAK di-retry
     * (5xx/timeout/429), lakukan beberapa percobaan tambahan tanpa blocking sleep
     * panjang. Kegagalan total mengembalikan null — artikel tetap dipublikasikan
     * tanpa gambar unggulan (tidak mematikan alur publish).
     *
     * @param  array{body: string, mime: string, filename: string}|null  $imagePlan
     * @param  array<string, Response|\Throwable>  $responses
     */
    private function resolveImageFromWave(WPSite $site, Article $article, ?array $imagePlan, array $responses): ?int
    {
        if ($imagePlan === null) {
            return null;
        }

        $response = $responses['image'] ?? null;

        if ($response instanceof \Throwable || $response === null || ! $response->successful()) {
            return $this->retryImageUpload($site, $article, $imagePlan, $response);
        }

        $mediaId = (int) ($response->json('id') ?? 0);

        if ($mediaId > 0) {
            Log::info('[WPPublish] Image upload ok', [
                'article_id' => $article->id,
                'media_id' => $mediaId,
                'http_status' => $response->status(),
            ]);

            return $mediaId;
        }

        return $this->retryImageUpload($site, $article, $imagePlan, $response);
    }

    /**
     * @param  array{body: string, mime: string, filename: string}  $imagePlan
     * @param  Response|\Throwable|null  $firstResponse
     */
    private function retryImageUpload(WPSite $site, Article $article, array $imagePlan, $firstResponse): ?int
    {
        $status = $firstResponse instanceof Response ? $firstResponse->status() : null;

        $retryable = $status === null || in_array($status, [408, 425, 429, 500, 502, 503, 504], true);

        if (! $retryable) {
            Log::warning('Upload gambar WP tidak di-retry (non-retryable HTTP '.($status ?? 'n/a').').', [
                'article_id' => $article->id,
            ]);

            return null;
        }

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            // Backoff singkat & tidak memblokir worker lama (300ms, 600ms — bukan 2/4/8s).
            usleep(300_000 * $attempt);

            try {
                // Client FRESH per percobaan: PendingRequest mutable & persisten.
                $response = $this->client($site)
                    ->timeout(120)
                    ->withBody($imagePlan['body'], $imagePlan['mime'])
                    ->withHeaders(['Content-Disposition' => 'attachment; filename="'.$imagePlan['filename'].'"'])
                    ->post('/media');

                $response->throw();

                $mediaId = (int) ($response->json('id') ?? 0);

                if ($mediaId > 0) {
                    Log::info('[WPPublish] Image upload ok (retry)', [
                        'article_id' => $article->id,
                        'attempt' => $attempt,
                        'media_id' => $mediaId,
                    ]);

                    return $mediaId;
                }
            } catch (\Throwable $e) {
                Log::warning('Retry upload gambar WP gagal', [
                    'article_id' => $article->id,
                    'attempt' => $attempt,
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                ]);
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
     * Petakan nama term ke ID dari hasil wave paralel; term yang belum ada
     * (atau gagal ter-cocokkan) dikembalikan sebagai daftar "to-create" yang
     * dibuat lewat POST setelahnya. Hasil positif di-cache 7 hari.
     *
     * @param  array<string, array{cache: bool, id: int|null, key: string}>  $lookups
     * @param  array<string, Response|\Throwable>  $responses
     * @return array{0: array<int, int>, 1: array<int, string>}
     */
    private function resolveTermsFromWave(WPSite $site, string $taxonomy, array $lookups, array $responses): array
    {
        $ids = [];
        $toCreate = [];

        foreach ($lookups as $name => $plan) {
            if ($plan['cache']) {
                if ($plan['id'] !== null) {
                    $ids[] = $plan['id'];
                }

                continue;
            }

            $response = $responses[$taxonomy.':'.$name] ?? null;

            if ($response instanceof \Throwable) {
                Log::warning('[WPPublish] Term lookup failed', [
                    'taxonomy' => $taxonomy,
                    'name' => $name,
                    'exception' => get_class($response),
                    'message' => $response->getMessage(),
                ]);

                // Abaikan kegagalan lookup satu term — tidak menggagalkan publikasi.
                continue;
            }

            if ($response && $response->successful()) {
                $existing = collect($response->json())->firstWhere('name', $name)['id'] ?? null;

                if ($existing) {
                    $ids[] = (int) $existing;
                    $this->cacheTermId($site->id, $taxonomy, $name, (int) $existing);

                    Log::info('[WPPublish] Term found', ['taxonomy' => $taxonomy, 'name' => $name, 'id' => $existing]);

                    continue;
                }
            }

            // Pencarian tidak menemukan term → perlu dibuat via POST.
            $toCreate[] = $name;
        }

        return [array_values(array_unique($ids)), array_values(array_unique($toCreate))];
    }

    private function cacheTermId(int $siteId, string $taxonomy, string $name, int $id): void
    {
        Cache::put("wp_term_id_site_{$siteId}_{$taxonomy}_".mb_strtolower($name), $id, now()->addDays(7));
    }

    /**
     * Buat term kategori/tag di situs target. Kegagalan (HTTP error atau
     * exception) hanya dicatat log dan mengembalikan null — tidak menggagalkan
     * seluruh publikasi.
     */
    private function createTerm(PendingRequest $client, string $taxonomy, string $name): ?int
    {
        try {
            $created = $client->post("/{$taxonomy}", ['name' => $name]);

            if ($created->successful()) {
                Log::info('[WPPublish] Term created', ['taxonomy' => $taxonomy, 'name' => $name, 'id' => $created->json('id')]);

                return (int) $created->json('id');
            }

            Log::warning('[WPPublish] Term create failed', [
                'taxonomy' => $taxonomy,
                'name' => $name,
                'http_status' => $created->status(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('[WPPublish] Term create exception', [
                'taxonomy' => $taxonomy,
                'name' => $name,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);
        }

        return null;
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
