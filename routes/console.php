<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduler
|--------------------------------------------------------------------------
|
| Penjadwal publish artikel berjalan tiap menit. Klaim internal pada
| ArticleService::publishDue() menjamin idempoten; tanpa overlap & terkunci
| satu server (database cache mendukung atomic lock).
|
*/
Schedule::command('articles:publish-due')
    ->everyMinute()
    ->withoutOverlapping(10)
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/scheduler.log'));
