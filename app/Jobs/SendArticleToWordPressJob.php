<?php

namespace App\Jobs;

use App\Models\Article;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * @deprecated Gunakan PublishArticleToWordPressJob (per-situs). Job ini dipertahankan
 * sebagai kompatibilitas: ia mem-fan-out satu job per situs publikasi artikel.
 */
class SendArticleToWordPressJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $articleId)
    {
    }

    public function handle(): void
    {
        $article = Article::withoutGlobalScopes()
            ->with('sitePublications')
            ->findOrFail($this->articleId);

        foreach ($article->sitePublications as $publication) {
            PublishArticleToWordPressJob::dispatch($article->id, (int) $publication->wp_site_id);
        }
    }
}
