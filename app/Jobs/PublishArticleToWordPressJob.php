<?php

namespace App\Jobs;

use App\Models\Article;
use App\Models\ArticleSitePublication;
use App\Models\ArticleWPLog;
use App\Models\WPSite;
use App\Services\WordPressPublisherService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class PublishArticleToWordPressJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60];

    public function __construct(
        public readonly int $articleId,
        public readonly int $wpSiteId,
    ) {
    }

    public function handle(WordPressPublisherService $publisher): void
    {
        // withoutGlobalScopes: worker antrean tidak punya konteks Auth, jadi TenantScope
        // harus dilewati agar artikel & situs tetap dapat dimuat.
        $article = Article::withoutGlobalScopes()
            ->with(['author', 'seoMeta', 'categories', 'tags'])
            ->findOrFail($this->articleId);

        $site = WPSite::withoutGlobalScopes()->findOrFail($this->wpSiteId);

        try {
            $result = $publisher->publish($article, $site);

            $this->updatePublication($article, $site, [
                'wp_post_id' => $result['wp_post_id'] ?? null,
                'wp_media_id' => $result['wp_media_id'] ?? null,
                'status' => 'published',
                'response_message' => $result['message'] ?? 'Published successfully.',
                'synced_at' => now(),
            ]);

            $this->log($article, $site, 'success', $result['wp_post_id'] ?? null, $result['message'] ?? 'Published successfully.');
        } catch (Throwable $throwable) {
            $this->updatePublication($article, $site, [
                'status' => 'failed',
                'response_message' => $throwable->getMessage(),
                'synced_at' => now(),
            ]);

            $this->log($article, $site, 'failed', null, $throwable->getMessage());

            $this->recomputeArticleStatus($article);

            throw $throwable;
        }

        $this->recomputeArticleStatus($article);
    }

    /**
     * Tandai publikasi gagal permanen setelah seluruh percobaan habis.
     */
    public function failed(Throwable $exception): void
    {
        $article = Article::withoutGlobalScopes()->find($this->articleId);
        $site = WPSite::withoutGlobalScopes()->find($this->wpSiteId);

        if (! $article || ! $site) {
            return;
        }

        $this->updatePublication($article, $site, [
            'status' => 'failed',
            'response_message' => $exception->getMessage(),
            'synced_at' => now(),
        ]);

        $this->recomputeArticleStatus($article);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function updatePublication(Article $article, WPSite $site, array $attributes): void
    {
        ArticleSitePublication::updateOrCreate(
            ['article_id' => $article->id, 'wp_site_id' => $site->id],
            $attributes,
        );
    }

    private function log(Article $article, WPSite $site, string $status, ?int $wpPostId, string $message): void
    {
        ArticleWPLog::create([
            'article_id' => $article->id,
            'wp_site_id' => $site->id,
            'wp_post_id' => $wpPostId,
            'status' => $status,
            'response_message' => $message,
            'synced_at' => now(),
        ]);
    }

    /**
     * Status artikel = agregat dari seluruh publikasi situs:
     * - semua published  => published
     * - ada yang failed   => failed
     * - selain itu        => queued
     */
    private function recomputeArticleStatus(Article $article): void
    {
        $statuses = ArticleSitePublication::where('article_id', $article->id)->pluck('status');

        if ($statuses->isEmpty()) {
            return;
        }

        $status = match (true) {
            $statuses->every(fn ($s) => $s === 'published') => 'published',
            $statuses->contains('failed') => 'failed',
            default => 'queued',
        };

        $article->update(['status' => $status]);
    }
}
