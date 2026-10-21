<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dateTime('scheduled_at')->nullable()->after('status');
        });

        // Perluas enum status dengan 'scheduled'. change() ditangani natif oleh
        // Laravel 12 (MySQL: ALTER MODIFY, SQLite: rebuild tabel) — tanpa doctrine.
        Schema::table('articles', function (Blueprint $table) {
            $table->enum('status', ['draft', 'scheduled', 'queued', 'published', 'failed'])
                ->default('draft')
                ->change();
        });

        Schema::table('articles', function (Blueprint $table) {
            // Query dispatcher jatuh tempo: WHERE status = 'scheduled' AND due.
            $table->index(['status', 'scheduled_at']);
            // Query kalender per perusahaan (modul artikel berbasis ArticleContext).
            $table->index(['company_id', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndex(['status', 'scheduled_at']);
            $table->dropIndex(['company_id', 'scheduled_at']);
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->enum('status', ['draft', 'queued', 'published', 'failed'])
                ->default('draft')
                ->change();
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('scheduled_at');
        });
    }
};
