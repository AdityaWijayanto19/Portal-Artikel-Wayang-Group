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
| PENTING: memakai Schedule::call() (callback) BUKAN Schedule::command().
| Schedule::command() menjalankan command sebagai subprocess lewat Symfony
| Process yang membutuhkan proc_open() — Hostinger menonaktifkan proc_open()
| di PHP CLI shared hosting sehingga event command gagal diam-diam tiap menit
| (lihat laravel.log: "The Process class relies on proc_open"). Callback
| dieksekusi in-process, tanpa subprocess. Name() wajib agar tanpa overlap
| aktif & mutex punya nama unik.
|
*/
Schedule::call(function () {
    Artisan::call('articles:publish-due');
})->name('articles:publish-due')
    ->everyMinute()
    ->withoutOverlapping(10)
    ->onOneServer();
