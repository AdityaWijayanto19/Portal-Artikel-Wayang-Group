<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Services\SeoAnalyzerService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Baris article_seo_meta yang artikelnya sudah tidak ada (meta yatim) — biasanya
 * bekas impor dump dengan FOREIGN_KEY_CHECKS=OFF. Cek duplikasi keyphrase tidak
 * boleh menghitung baris ini: kalau iya, artikel baru yang memakai keyphrase sama
 * dianggap duplikat selamanya padahal pembandingnya sudah tidak ada, dan skor
 * indikator #13 (Previously Used Keyphrase) hilang 8 poin tanpa jalan keluar.
 *
 * Kelas ini sengaja TIDAK memakai RefreshDatabase karena PRAGMA foreign_keys
 * adalah no-op di dalam transaksi, sedangkan skenario ini butuh menulis baris
 * yang melanggar FK secara sengaja. Maka itu tabel di-fresh manual (migrate:fresh)
 * pada setUp & tearDown: deterministik, tidak meninggalkan sisa data untuk tes
 * lain, dan tetap menyediakan tabel bagi tes yang tidak memakai trait migrasi.
 */
class SeoAnalyzerOrphanMetaTest extends TestCase
{
    /**
     * article_id yang sudah tidak ada di tabel articles (yatim).
     */
    private const ORPHAN_ARTICLE_ID = 999999;

    private SeoAnalyzerService $analyzer;

    private ?Company $company = null;

    private ?User $author = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->freshDatabase();

        $this->analyzer = $this->app->make(SeoAnalyzerService::class);
    }

    protected function tearDown(): void
    {
        // Bersihkan seluruh data uji sebelum tes berikutnya — termasuk baris yatim
        // dan perusahaan/penulis yang dipakai endpoint check-keyword.
        $this->freshDatabase();

        parent::tearDown();
    }

    // =====================================================================
    // Indikator #13: Previously Used Keyphrase (8 poin)
    // =====================================================================

    public function test_meta_whose_article_is_gone_is_not_counted_as_duplicate(): void
    {
        $this->insertOrphanMeta('karhutla');

        $result = $this->analyzer->analyze([
            'title' => 'Judul Baru Tanpa Artikel Lama',
            'slug' => 'judul-baru-tanpa-artikel-lama',
            'content' => '<p>'.str_repeat('Konten artikel baru yang menjelaskan kondisi terkini di lapangan. ', 20).'</p>',
            'yoast_title' => 'Judul Baru Tanpa Artikel Lama',
            'yoast_metadesc' => 'Deskripsi singkat artikel baru.',
            'yoast_focuskw' => 'karhutla',
            'image_alt_text' => 'Karhutla',
        ]);

        $this->assertSame(
            8,
            $this->indicator($result['seo_breakdown'], 'Previously Used Keyphrase'),
            'Meta yatim tidak boleh membuat keyphrase dianggap duplikat.',
        );
    }

    /**
     * Endpoint adalah satu-satunya sumber data kalkulator JS di form. Kondisi
     * score_duplicate harus ikut mengabaikan meta yatim, jika tidak panel editor
     * kembali menampilkan skor 8 poin lebih rendah dari kenyataan.
     */
    public function test_keyword_check_endpoint_ignores_orphan_meta(): void
    {
        $this->insertOrphanMeta('karhutla');

        $author = $this->makeAuthor();

        $this->actingAs($author)
            ->get(route('articles.check-keyword', ['keyword' => 'karhutla']))
            ->assertOk()
            ->assertJson(['duplicate' => false, 'score_duplicate' => false]);
    }

    // =====================================================================
    // Helpers
    // =====================================================================

    /**
     * Sisipkan baris SEO meta yang artikelnya sudah tidak ada.
     *
     * FK dimatikan di luar transaksi; bila ternyata masih aktif, insert ini akan
     * melempar "FOREIGN KEY constraint failed" sehingga tes gagal jelas (artinya
     * skenario yatimnya tidak tercapai), bukan diam-diam lolos.
     */
    private function insertOrphanMeta(string $focusKeyword): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        DB::table('article_seo_meta')->insert([
            'article_id' => self::ORPHAN_ARTICLE_ID,
            'yoast_title' => 'Judul Artikel Lama',
            'yoast_metadesc' => 'Deskripsi artikel lama.',
            'yoast_focuskw' => $focusKeyword,
            'seo_score' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::statement('PRAGMA foreign_keys = ON');

        $this->assertDatabaseMissing('articles', ['id' => self::ORPHAN_ARTICLE_ID]);
        $this->assertDatabaseHas('article_seo_meta', ['article_id' => self::ORPHAN_ARTICLE_ID]);
    }

    private function makeAuthor(): User
    {
        foreach (['super_admin', 'admin', 'author'] as $role) {
            Role::findOrCreate($role);
        }

        $this->company = Company::create(['name' => 'PT Contoh Yatim', 'slug' => 'pt-contoh-yatim']);

        $this->author = User::create([
            'name' => 'Penulis Uji',
            'email' => 'penulis-uji@example.com',
            'username' => 'penulis-uji',
            'password' => bcrypt('password'),
            'company_id' => $this->company->id,
        ]);
        $this->author->assignRole('author');

        return $this->author;
    }

    private function freshDatabase(): void
    {
        if (! $this->app) {
            return;
        }

        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
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
