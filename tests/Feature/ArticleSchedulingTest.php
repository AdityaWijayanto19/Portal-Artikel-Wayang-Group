<?php

namespace Tests\Feature;

use App\Jobs\PublishArticleToWordPressJob;
use App\Models\Article;
use App\Models\ArticleSitePublication;
use App\Models\Category;
use App\Models\Company;
use App\Models\Scopes\TenantScope;
use App\Models\User;
use App\Models\WPSite;
use App\Services\ArticleService;
use App\Services\SeoAnalyzerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Pengujian fitur scheduling upload (jadwalkan publish ke WordPress):
 * validasi waktu, gerbang skor, klaim idempoten dispatcher, isolasi tenant,
 * guard status scheduled, dan endpoint reschedule/cancel.
 */
class ArticleSchedulingTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Category $category;

    private User $author;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'admin', 'author'] as $role) {
            Role::findOrCreate($role);
        }

        $this->company = Company::create(['name' => 'PT Contoh', 'slug' => 'pt-contoh']);
        $this->category = Category::create([
            'company_id' => $this->company->id,
            'name' => 'Tips',
            'slug' => 'tips',
        ]);

        $this->author = User::create([
            'name' => 'Author',
            'email' => 'author@example.com',
            'username' => 'author',
            'password' => bcrypt('password'),
            'company_id' => $this->company->id,
        ]);
        $this->author->assignRole('author');

        // Fokus pengujian = logika penjadwalan, bukan perhitungan skor SEO.
        $this->mockSeoScores(90, 92);
    }

    /**
     * Bind mock SeoAnalyzerService agar lolos gerbang skor (≥ 80) secara deterministik.
     */
    private function mockSeoScores(int $seoScore, int $readabilityScore): void
    {
        $this->mock(SeoAnalyzerService::class, fn ($mock) => $mock
            ->shouldReceive('analyze')
            ->andReturn([
                'seo_score' => $seoScore,
                'readability_score' => $readabilityScore,
                'estimated_reading_time_minutes' => 5,
            ]));
    }

    /**
     * Buat artikel lengkap dengan satu baris publikasi situs target (status draft).
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $publicationAttributes
     */
    private function makeArticle(array $attributes = [], array $publicationAttributes = []): Article
    {
        $site = WPSite::factory()->create(['company_id' => $this->company->id]);

        $article = Article::create(array_merge([
            'company_id' => $this->company->id,
            'user_id' => $this->author->id,
            'wp_site_id' => $site->id,
            'category_id' => $this->category->id,
            'title' => 'Artikel Uji Penjadwalan Publish',
            'slug' => 'artikel-uji-penjadwalan-'.uniqid(),
            'content' => str_repeat('Konten artikel untuk pengujian penjadwalan publish otomatis. ', 20),
            'seo_score' => 90,
            'readability_score' => 90,
            'status' => 'draft',
        ], $attributes));

        ArticleSitePublication::create(array_merge([
            'article_id' => $article->id,
            'wp_site_id' => $site->id,
            'status' => 'draft',
        ], $publicationAttributes));

        return $article->refresh();
    }

    // =====================================================================
    // Service: schedule / reschedule
    // =====================================================================

    public function test_schedule_sets_status_and_time(): void
    {
        $article = $this->makeArticle();
        $at = now()->addHour()->startOfMinute();

        app(ArticleService::class)->schedule($article, $at);

        $article->refresh();
        $this->assertSame('scheduled', $article->status);
        $this->assertTrue($article->scheduled_at->equalTo($at));
    }

    public function test_schedule_reschedules_existing_schedule(): void
    {
        $article = $this->makeArticle();
        $service = app(ArticleService::class);

        $service->schedule($article, now()->addHour());
        $newAt = now()->addHours(3)->startOfMinute();
        $service->schedule($article->refresh(), $newAt);

        $article->refresh();
        $this->assertSame('scheduled', $article->status);
        $this->assertTrue($article->scheduled_at->equalTo($newAt));
    }

    public function test_schedule_rejects_low_seo_score(): void
    {
        $this->mockSeoScores(50, 90);

        $this->expectException(ValidationException::class);

        app(ArticleService::class)->schedule($this->makeArticle(), now()->addHour());
    }

    public function test_schedule_rejects_low_readability_score(): void
    {
        $this->mockSeoScores(90, 30);

        $this->expectException(ValidationException::class);

        app(ArticleService::class)->schedule($this->makeArticle(), now()->addHour());
    }

    public function test_schedule_rejects_near_past_and_beyond_horizon_times(): void
    {
        $service = app(ArticleService::class);
        $article = $this->makeArticle();

        foreach ([now()->subMinute(), now()->addMinute(), now()->addYears(2)] as $at) {
            try {
                $service->schedule($article, $at);
                $this->fail('Seharusnya melempar ValidationException untuk waktu '.$at->toDateTimeString());
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('scheduled_at', $e->errors());
            }
        }

        $this->assertSame('draft', $article->refresh()->status);
    }

    // =====================================================================
    // Service: cancel
    // =====================================================================

    public function test_cancel_schedule_restores_draft_and_clears_time(): void
    {
        $article = $this->makeArticle();
        $service = app(ArticleService::class);

        $service->schedule($article, now()->addHour());
        $service->cancelSchedule($article->refresh());

        $article->refresh();
        $this->assertSame('draft', $article->status);
        $this->assertNull($article->scheduled_at);
    }

    public function test_cancel_schedule_on_non_scheduled_article_is_rejected(): void
    {
        $article = $this->makeArticle();

        $this->expectException(ValidationException::class);

        app(ArticleService::class)->cancelSchedule($article);
    }

    // =====================================================================
    // Service: dispatcher jatuh tempo (publishDue)
    // =====================================================================

    public function test_publish_due_dispatches_job_exactly_once_for_due_article(): void
    {
        Queue::fake();

        $article = $this->makeArticle([
            'status' => 'scheduled',
            'scheduled_at' => now()->subMinutes(2),
        ]);

        $processed = app(ArticleService::class)->publishDue();

        $this->assertSame([$article->id], $processed);
        Queue::assertPushed(PublishArticleToWordPressJob::class, 1);

        $article->refresh();
        $this->assertSame('queued', $article->status);
        $this->assertNull($article->scheduled_at, 'Jadwal aktif dibersihkan setelah diantrekan.');

        // Run berikutnya (cron per-menit) tidak boleh menggandakan dispatch.
        app(ArticleService::class)->publishDue();
        Queue::assertPushed(PublishArticleToWordPressJob::class, 1);
    }

    public function test_publish_due_skips_schedules_in_the_future(): void
    {
        Queue::fake();

        $article = $this->makeArticle([
            'status' => 'scheduled',
            'scheduled_at' => now()->addHours(2),
        ]);

        $processed = app(ArticleService::class)->publishDue();

        $this->assertSame([], $processed);
        Queue::assertNothingPushed();
        $this->assertSame('scheduled', $article->refresh()->status);
    }

    public function test_publish_due_marks_failed_when_execution_gate_rejects(): void
    {
        Queue::fake();

        // Skor turun setelah dijadwalkan (mis. konten diedit) → gate jatuh tempo menolak.
        $this->mockSeoScores(40, 50);

        $article = $this->makeArticle([
            'status' => 'scheduled',
            'scheduled_at' => now()->subMinute(),
        ]);

        $processed = app(ArticleService::class)->publishDue();

        $this->assertSame([], $processed);
        Queue::assertNothingPushed();
        $this->assertSame('failed', $article->refresh()->status);
    }

    // =====================================================================
    // Guard: status scheduled tidak boleh ditimpa agregat publikasi
    // =====================================================================

    public function test_deleting_site_publication_keeps_scheduled_status(): void
    {
        $article = $this->makeArticle();
        $service = app(ArticleService::class);

        $service->schedule($article, now()->addHour());
        $article->refresh();

        $service->deleteSitePublication($article, $article->sitePublications()->firstOrFail());

        $this->assertSame('scheduled', $article->refresh()->status);
    }

    // =====================================================================
    // Service: event kalender
    // =====================================================================

    public function test_calendar_events_include_scheduled_published_and_failed(): void
    {
        $scheduled = $this->makeArticle([
            'status' => 'scheduled',
            'scheduled_at' => now()->addHour()->startOfMinute(),
        ]);
        $published = $this->makeArticle(['status' => 'published'], [
            'status' => 'published',
            'synced_at' => now()->subHour(),
        ]);
        $failed = $this->makeArticle(['status' => 'failed']);

        $events = app(ArticleService::class)->calendarEvents(
            $this->company->id,
            now()->startOfMonth(),
            now()->endOfMonth(),
        );

        $this->assertCount(3, $events);
        $this->assertSame(
            ['failed', 'published', 'scheduled'],
            collect($events)->pluck('status')->sort()->values()->all(),
        );
        $this->assertSame(
            [$published->id, $failed->id, $scheduled->id],
            collect($events)->sortBy('start')->pluck('article_id')->values()->all(),
        );
    }

    // =====================================================================
    // HTTP: endpoint reschedule & cancel (isolasi tenant via ArticleContext)
    // =====================================================================

    public function test_author_can_reschedule_via_endpoint(): void
    {
        $article = $this->makeArticle([
            'status' => 'scheduled',
            'scheduled_at' => now()->addHour()->startOfMinute(),
        ]);

        $newAt = now()->addHours(3)->startOfMinute();

        $this->actingAs($this->author)
            ->patchJson(route('articles.schedule.update', $article), [
                'scheduled_at' => $newAt->format('Y-m-d H:i:s'),
            ])
            ->assertOk()
            ->assertJsonStructure(['message', 'scheduled_at']);

        $this->assertTrue($article->refresh()->scheduled_at->equalTo($newAt));
    }

    public function test_author_can_cancel_schedule_via_endpoint(): void
    {
        $article = $this->makeArticle([
            'status' => 'scheduled',
            'scheduled_at' => now()->addHour(),
        ]);

        $this->actingAs($this->author)
            ->deleteJson(route('articles.schedule.cancel', $article))
            ->assertOk();

        $article->refresh();
        $this->assertSame('draft', $article->status);
        $this->assertNull($article->scheduled_at);
    }

    public function test_schedule_endpoints_are_tenant_isolated(): void
    {
        $otherCompany = Company::create(['name' => 'PT Lain', 'slug' => 'pt-lain']);
        $otherCategory = Category::create([
            'company_id' => $otherCompany->id,
            'name' => 'Bisnis',
            'slug' => 'bisnis',
        ]);
        $otherSite = WPSite::factory()->create(['company_id' => $otherCompany->id]);

        $foreign = Article::create([
            'company_id' => $otherCompany->id,
            'user_id' => $this->author->id,
            'wp_site_id' => $otherSite->id,
            'category_id' => $otherCategory->id,
            'title' => 'Artikel Perusahaan Lain',
            'slug' => 'artikel-perusahaan-lain',
            'content' => str_repeat('Konten artikel pada perusahaan lain untuk uji isolasi tenant. ', 10),
            'status' => 'draft',
        ]);

        $this->actingAs($this->author)
            ->patchJson(route('articles.schedule.update', $foreign), [
                'scheduled_at' => now()->addHour()->format('Y-m-d H:i:s'),
            ])
            ->assertNotFound();

        $this->actingAs($this->author)
            ->deleteJson(route('articles.schedule.cancel', $foreign))
            ->assertNotFound();

        $this->assertSame('draft', $foreign->refresh()->status);
    }

    // =====================================================================
    // HTTP: alur form (action=schedule)
    // =====================================================================

    public function test_store_with_schedule_action_creates_scheduled_article(): void
    {
        $site = WPSite::factory()->create(['company_id' => $this->company->id]);
        $at = now()->addHours(2)->startOfMinute();

        $this->actingAs($this->author)->post(route('articles.store'), [
            'company_id' => $this->company->id,
            'user_id' => $this->author->id,
            'title' => 'Artikel Terjadwal Dari Form',
            'slug' => 'artikel-terjadwal-dari-form',
            'content' => str_repeat('Isi konten yang panjang untuk memenuhi aturan minimal dua ratus karakter. ', 4),
            'categories' => [$this->category->id],
            'wp_site_ids' => [$site->id],
            'status' => 'published',
            'action' => 'schedule',
            'scheduled_at' => $at->format('Y-m-d H:i:s'),
        ])->assertRedirect(route('articles.index'));

        $article = Article::withoutGlobalScope(TenantScope::class)
            ->where('slug', 'artikel-terjadwal-dari-form')
            ->firstOrFail();

        $this->assertSame('scheduled', $article->status);
        $this->assertTrue($article->scheduled_at->equalTo($at));
    }

    public function test_store_with_schedule_action_and_near_time_shows_validation_error(): void
    {
        $site = WPSite::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->author)->post(route('articles.store'), [
            'company_id' => $this->company->id,
            'user_id' => $this->author->id,
            'title' => 'Artikel Jadwal Terlalu Dekat',
            'slug' => 'artikel-jadwal-terlalu-dekat',
            'content' => str_repeat('Isi konten yang panjang untuk memenuhi aturan minimal dua ratus karakter. ', 4),
            'categories' => [$this->category->id],
            'wp_site_ids' => [$site->id],
            'status' => 'published',
            'action' => 'schedule',
            'scheduled_at' => now()->addMinute()->format('Y-m-d H:i:s'),
        ])->assertSessionHasErrors('scheduled_at');

        // Artikel tersimpan sebagai draft, tanpa jadwal aktif.
        $article = Article::withoutGlobalScope(TenantScope::class)
            ->where('slug', 'artikel-jadwal-terlalu-dekat')
            ->firstOrFail();

        $this->assertSame('draft', $article->status);
        $this->assertNull($article->scheduled_at);
    }
}
