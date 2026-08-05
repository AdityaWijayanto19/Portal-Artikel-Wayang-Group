<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_site_publications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
            $table->foreignId('wp_site_id')->constrained('wp_sites')->cascadeOnDelete();
            $table->unsignedBigInteger('wp_post_id')->nullable();
            $table->unsignedBigInteger('wp_media_id')->nullable();
            $table->enum('status', ['draft', 'queued', 'published', 'failed'])->default('draft');
            $table->text('response_message')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['article_id', 'wp_site_id']);
            $table->index(['wp_site_id', 'status']);
        });

        // Data migration: seed satu baris publikasi per artikel dari kolom tunggal articles.wp_site_id.
        if (Schema::hasColumn('articles', 'wp_site_id')) {
            $rows = DB::table('articles')
                ->whereNotNull('wp_site_id')
                ->select('id', 'wp_site_id', 'status')
                ->get();

            foreach ($rows as $row) {
                DB::table('article_site_publications')->insert([
                    'article_id' => $row->id,
                    'wp_site_id' => $row->wp_site_id,
                    'wp_post_id' => null,
                    'wp_media_id' => null,
                    'status' => in_array($row->status, ['draft', 'queued', 'published', 'failed'], true)
                        ? $row->status
                        : 'draft',
                    'response_message' => null,
                    'synced_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('article_site_publications');
    }
};
