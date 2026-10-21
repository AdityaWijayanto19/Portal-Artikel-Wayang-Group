<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Melengkapi tabel sub_categories yang dirujuk FK articles.sub_category_id.
 *
 * Riwayat migrasi repo tidak pernah membuat tabel ini (FK-nya ada di
 * 2026_07_30_000004_create_articles_and_article_wp_logs_tables), padahal MySQL
 * menolak membuat FK ke tabel yang belum ada. Di database lama tabel ini sudah
 * dibuat di luar migrasi, sehingga ada guard hasTable(): bila sudah ada, dilewati
 * dan tidak ada perubahan apa pun.
 *
 * Timestamp sengaja diletakkan SEBELUM migration articles agar `migrate:fresh`
 * di MySQL tidak gagal saat FK articles dibuat.
 *
 * down() sengaja tidak menghapus apa pun: pada database lama tabel ini tidak
 * dibuat oleh migrasi ini, sehingga menghapusnya berarti menghilangkan data.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sub_categories')) {
            return;
        }

        Schema::create('sub_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->timestamps();

            $table->unique(['category_id', 'slug']);
        });
    }

    public function down(): void
    {
        // Tidak ada yang dibatalkan — lihat docblock di atas.
    }
};
