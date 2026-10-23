<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ArticleSeoMeta;
use App\Models\ArticleSitePublication;
use App\Models\Category;
use App\Models\Company;
use App\Models\User;
use App\Models\WPSite;
use App\Services\SeoAnalyzerService;
use App\Services\WordPressPublisherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Kontrak skor SEO sisi server (SeoAnalyzerService) — sumber kebenaran gerbang
 * publish (≥ 80). Skor ini wajib sinkron dengan kalkulator JS pada
 * resources/views/articles/partials/_form.blade.php, terutama indikator
 * #9 (Keyphrase in Image Alt) dan #13 (Previously Used Keyphrase) yang dulu
 * di-hardcode di klien sehingga panel bisa menampilkan skor 8 poin lebih tinggi
 * daripada yang dihitung server.
 */
class SeoAnalyzerScoreTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $author;

    private Category $category;

    private WPSite $site;

    private SeoAnalyzerService $analyzer;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'admin', 'author'] as $role) {
            Role::findOrCreate($role);
        }

        $this->company = Company::create(['name' => 'PT Contoh', 'slug' => 'pt-contoh']);
        $this->category = Category::factory()->create(['company_id' => $this->company->id]);
        $this->site = WPSite::factory()->create(['company_id' => $this->company->id]);

        $this->author = User::create([
            'name' => 'Penulis Uji',
            'email' => 'penulis-uji@example.com',
            'username' => 'penulis-uji',
            'password' => bcrypt('password'),
            'company_id' => $this->company->id,
        ]);
        $this->author->assignRole('author');

        $this->analyzer = $this->app->make(SeoAnalyzerService::class);
    }

    // =====================================================================
    // Indikator #13: Previously Used Keyphrase (8 poin)
    // =====================================================================

    public function test_duplicate_focus_keyword_scores_zero(): void
    {
        $this->makeArticle('Artikel Lain', ['yoast_focuskw' => 'karhutla']);

        $result = $this->analyzer->analyze($this->input(['yoast_focuskw' => 'karhutla']));

        $this->assertSame(
            0,
            $this->indicator($result['seo_breakdown'], 'Previously Used Keyphrase'),
            'Keyphrase yang sudah dipakai artikel lain harus 0/8 di server.',
        );
    }

    public function test_unique_focus_keyword_scores_full_eight(): void
    {
        $this->makeArticle('Artikel Lain', ['yoast_focuskw' => 'karhutla']);

        $result = $this->analyzer->analyze($this->input(['yoast_focuskw' => 'el nino 2026']));

        $this->assertSame(
            8,
            $this->indicator($result['seo_breakdown'], 'Previously Used Keyphrase'),
            'Keyphrase unik harus 8/8 di server.',
        );
    }

    public function test_own_keyword_is_not_counted_as_duplicate(): void
    {
        $article = $this->makeArticle('Artikel Ini Sendiri', ['yoast_focuskw' => 'karhutla']);

        $result = $this->analyzer->analyze($this->input([
            'yoast_focuskw' => 'karhutla',
            'article_id' => $article->id,
        ]));

        $this->assertSame(8, $this->indicator($result['seo_breakdown'], 'Previously Used Keyphrase'));
    }

    /**
     * Endpoint check-keyword harus melaporkan dua hal terpisah: peringatan duplikat
     * (perusahaan aktif) dan kondisi PENILAIAN skor (identik dengan analyzer).
     * Tanpa pemisahan ini, kalkulator JS menampilkan skor 8 poin di atas gerbang publish
     * ketika keyword dipakai artikel perusahaan LAIN.
     */
    public function test_keyword_check_endpoint_reports_scoring_duplicate_across_companies(): void
    {
        $otherCompany = Company::create(['name' => 'PT Lain', 'slug' => 'pt-lain']);
        $otherAuthor = User::create([
            'name' => 'Penulis Lain',
            'email' => 'penulis-lain@example.com',
            'username' => 'penulis-lain',
            'password' => bcrypt('password'),
            'company_id' => $otherCompany->id,
        ]);
        $otherAuthor->assignRole('author');

        $this->makeArticle('Artikel Perusahaan Lain', ['yoast_focuskw' => 'karhutla'], [
            'company_id' => $otherCompany->id,
            'user_id' => $otherAuthor->id,
            'wp_site_id' => WPSite::factory()->create(['company_id' => $otherCompany->id])->id,
            'category_id' => Category::factory()->create(['company_id' => $otherCompany->id])->id,
        ]);

        $own = $this->makeArticle('Artikel Perusahaan Kita', ['yoast_focuskw' => 'el nino 2026']);

        $response = $this->actingAs($this->author)
            ->get(route('articles.check-keyword', ['keyword' => 'karhutla', 'article_id' => $own->id]))
            ->assertOk()
            ->assertJson(['duplicate' => false, 'score_duplicate' => true]);

        // Kondisi yang dilaporkan endpoint = kondisi yang dipakai analyzer untuk menilai.
        $result = $this->analyzer->analyze($this->input([
            'yoast_focuskw' => 'karhutla',
            'article_id' => $own->id,
        ]));

        $this->assertSame(
            0,
            $this->indicator($result['seo_breakdown'], 'Previously Used Keyphrase'),
            'Analyzer harus memberi 0 saat endpoint melaporkan score_duplicate = true.',
        );
    }

    // =====================================================================
    // Indikator #9: Keyphrase in Image Alt (6 poin) tanpa keyphrase → 0
    // =====================================================================

    public function test_keyword_indicators_score_zero_when_focus_keyword_is_empty(): void
    {
        $result = $this->analyzer->analyze($this->input([
            'yoast_focuskw' => '',
            'image_alt_text' => 'karhutla',
        ]));

        $breakdown = $result['seo_breakdown'];

        $this->assertSame(
            0,
            $this->indicator($breakdown, 'Keyphrase in Image Alt'),
            'Tanpa keyphrase, alt text yang terisi tetap 0/6 di server.',
        );
        $this->assertSame(0, $this->indicator($breakdown, 'Previously Used Keyphrase'));
        $this->assertSame(0, $this->indicator($breakdown, 'Keyphrase in Meta Description'));
    }

    // =====================================================================
    // SEO Title kosong = memakai judul artikel (sesuai hint pada form)
    // =====================================================================

    public function test_empty_seo_title_falls_back_to_article_title(): void
    {
        $title = 'Sumatra hingga Papua Tampak Merah! Apa Kaitannya dengan El Niño 2026?';

        $fallback = $this->analyzer->analyze($this->input([
            'title' => $title,
            'yoast_title' => '',
        ]));
        $explicit = $this->analyzer->analyze($this->input([
            'title' => $title,
            'yoast_title' => $title,
        ]));

        $this->assertSame(
            $this->indicator($explicit['seo_breakdown'], 'SEO Title Length'),
            $this->indicator($fallback['seo_breakdown'], 'SEO Title Length'),
            'SEO Title kosong harus dinilai dari judul artikel, bukan 0.',
        );
        $this->assertSame($explicit['seo_score'], $fallback['seo_score']);
    }

    // =====================================================================
    // Paritas _yoast_wpseo_linkdex yang dikirim ke WordPress
    // =====================================================================

    /**
     * Saat publish, WordPress menerima _yoast_wpseo_linkdex dari analisis ULANG
     * oleh server (bukan skor tersimpan). Payload analisis dari
     * WordPressPublisherService::seoInput() wajib memuat article_id: tanpa itu
     * artikel INI dianggap sebagai pemakai keyphrase sendiri, indikator #13 dipotong,
     * dan skor yang muncul di dashboard Yoast selalu 8 poin di bawah skor portal.
     */
    public function test_yoast_linkdex_sent_to_wordpress_equals_portal_score(): void
    {
        Cache::flush();

        Http::fake([
            '*/wp-json/wp/v2/users*' => Http::response([
                ['id' => 7, 'username' => 'penulis-uji', 'slug' => 'penulis-uji', 'email' => 'penulis-uji@example.com'],
            ]),
            '*/wp-json/wp/v2/posts*' => fn ($request) => Http::response([
                'id' => 123,
                'link' => 'https://wp.example.net/?p=123',
                'meta' => data_get($request, 'meta', []),
            ]),
        ]);

        // Bukan target simulasi (.local / example.com / localhost) → alur publish nyata.
        $site = WPSite::factory()->create([
            'company_id' => $this->company->id,
            'site_url' => 'https://wp.example.net',
        ]);

        $article = $this->makeArticle('Artikel Karhutla', ['yoast_focuskw' => 'karhutla']);

        ArticleSitePublication::create([
            'article_id' => $article->id,
            'wp_site_id' => $site->id,
            'wp_post_id' => null,
            'status' => 'queued',
        ]);

        $publisher = $this->app->make(WordPressPublisherService::class);
        $result = $publisher->publish($article->refresh(), $site);

        $this->assertTrue($result['success'], (string) ($result['message'] ?? ''));
        $this->assertNull($result['meta_warning']);

        // Payload analisis ulang yang benar-benar dipakai publisher.
        $method = new \ReflectionMethod(WordPressPublisherService::class, 'seoInput');
        $method->setAccessible(true);
        $seoInput = $method->invoke($publisher, $article);

        $this->assertSame($article->id, $seoInput['article_id'] ?? null, 'seoInput wajib menyertakan article_id.');

        $expected = $this->analyzer->analyze($seoInput)['seo_score'];
        $withoutArticleId = $this->analyzer->analyze(Arr::except($seoInput, 'article_id'))['seo_score'];

        $this->assertSame(
            $expected - 8,
            $withoutArticleId,
            'Prasyarat: tanpa article_id skor analisis turun tepat 8 poin (indikator #13).',
        );

        $this->assertSame(
            $expected,
            $this->linkdexSentToWordPress(),
            '_yoast_wpseo_linkdex harus sama dengan skor portal, bukan 8 poin lebih rendah.',
        );
    }

    // =====================================================================
    // Helpers
    // =====================================================================

    /**
     * Buat artikel + baris SEO meta-nya (dipakai untuk memicu indikator #13).
     *
     * @param  array<string, mixed>  $seoMeta
     * @param  array<string, mixed>  $articleAttributes
     */
    private function makeArticle(string $title, array $seoMeta = [], array $articleAttributes = []): Article
    {
        $article = Article::create(array_merge([
            'company_id' => $this->company->id,
            'user_id' => $this->author->id,
            'wp_site_id' => $this->site->id,
            'category_id' => $this->category->id,
            'title' => $title,
            'slug' => 'artikel-uji-seo-'.uniqid(),
            'content' => $this->content(),
            'status' => 'draft',
        ], $articleAttributes));

        ArticleSeoMeta::create(array_merge([
            'article_id' => $article->id,
            'yoast_title' => $title,
            'yoast_focuskw' => 'karhutla',
            'seo_score' => 0,
        ], $seoMeta));

        return $article;
    }

    /**
     * Input analisis — meniru artikel pada laporan bug (focus keyphrase "karhutla").
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function input(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Sumatra hingga Papua Tampak Merah! Apa Kaitannya dengan El Niño 2026?',
            'slug' => 'sumatra-hingga-papua-tampak-merah-apa-kaitannya-dengan-el-nino-2026',
            'content' => $this->content(),
            'yoast_title' => 'Sumatra hingga Papua Tampak Merah! Apa Kaitannya dengan',
            'yoast_metadesc' => 'Sumatra hingga Papua masuk zona rawan kebakaran. Simak dampak El Niño 2026, cuaca panas, kenaikan titik panas, dan risiko karhutla di Indonesia.',
            'yoast_focuskw' => 'karhutla',
            'image_alt_text' => 'Karhutla',
        ], $overrides);
    }

    private function content(): string
    {
        return '<p>'.str_repeat('Peta Indonesia yang tampak merah menarik perhatian publik dan menjadi bahan pembahasan. ', 30).'</p>'
            .'<p><a href="/internal">internal</a> <a href="https://example.com">external</a></p>';
    }

    /**
     * Ambil nilai _yoast_wpseo_linkdex dari request meta Yoast terpisah
     * (WordPressPublisherService::updateYoastMeta → POST /posts/{id}).
     */
    private function linkdexSentToWordPress(): int
    {
        $metaRequest = collect(Http::recorded())
            ->map(fn (array $pair) => $pair[0])
            ->first(fn ($request) => str_contains((string) $request->body(), '_yoast_wpseo_linkdex'));

        $this->assertNotNull($metaRequest, 'Meta Yoast harus dikirim pada request terpisah setelah artikel tersimpan.');

        $meta = json_decode((string) $metaRequest->body(), true)['meta'] ?? [];

        $this->assertArrayHasKey('_yoast_wpseo_linkdex', $meta);

        return (int) $meta['_yoast_wpseo_linkdex'];
    }

    /**
     * Ambil skor satu indikator dari breakdown SEO.
     *
     * @param  array<int, array{label: string, score: int, max: int}>  $breakdown
     */
    private function indicator(array $breakdown, string $label): int
    {
        foreach ($breakdown as $item) {
            if (($item['label'] ?? null) === $label) {
                return (int) $item['score'];
            }
        }

        $this->fail("Indikator \"{$label}\" tidak ditemukan pada breakdown SEO.");
    }
}
