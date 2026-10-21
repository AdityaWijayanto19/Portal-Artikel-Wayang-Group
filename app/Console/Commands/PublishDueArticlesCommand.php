<?php

namespace App\Console\Commands;

use App\Services\ArticleService;
use Illuminate\Console\Command;

/**
 * Dispatcher jadwal publish: dijalankan tiap menit oleh Laravel Scheduler
 * (routes/console.php) dan mengantrekan semua artikel yang jadwalnya jatuh tempo.
 *
 * Pemanggilan aman dijalankan berulang — ArticleService::publishDue() mengklaim
 * lewat UPDATE bersyarat sehingga run bersamaan tidak menggandakan job.
 */
class PublishDueArticlesCommand extends Command
{
    protected $signature = 'articles:publish-due
        {--limit=50 : Jumlah artikel maksimal yang diklaim per run}';

    protected $description = 'Mengantrekan publish WordPress untuk artikel yang jadwalnya sudah jatuh tempo';

    public function handle(ArticleService $articleService): int
    {
        $processed = $articleService->publishDue((int) $this->option('limit'));

        $this->info(sprintf('[articles:publish-due] %d artikel diantrekan ke worker.', count($processed)));

        return self::SUCCESS;
    }
}
