<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_seo_meta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
            $table->string('yoast_title')->nullable();
            $table->text('yoast_metadesc')->nullable();
            $table->string('yoast_focuskw')->nullable();
            $table->unsignedInteger('seo_score')->default(0);
            $table->unsignedInteger('content_score')->nullable();
            $table->unsignedInteger('reading_time_minutes')->nullable();
            $table->timestamps();

            $table->unique('article_id');
        });

        // Data migration: salin data SEO dari kolom pada tabel articles yang sudah ada.
        if (Schema::hasColumn('articles', 'seo_score')) {
            $rows = DB::table('articles')
                ->select('id', 'yoast_title', 'yoast_metadesc', 'yoast_focuskw', 'seo_score')
                ->get();

            foreach ($rows as $row) {
                DB::table('article_seo_meta')->insert([
                    'article_id' => $row->id,
                    'yoast_title' => $row->yoast_title,
                    'yoast_metadesc' => $row->yoast_metadesc,
                    'yoast_focuskw' => $row->yoast_focuskw,
                    'seo_score' => $row->seo_score ?? 0,
                    'content_score' => null,
                    'reading_time_minutes' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('article_seo_meta');
    }
};
