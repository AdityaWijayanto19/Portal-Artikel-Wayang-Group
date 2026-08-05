<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['article_id', 'category_id']);
        });

        // Data migration: seed pivot dari kolom tunggal articles.category_id.
        if (Schema::hasColumn('articles', 'category_id')) {
            $rows = DB::table('articles')
                ->whereNotNull('category_id')
                ->select('id', 'category_id')
                ->get();

            foreach ($rows as $row) {
                DB::table('article_category')->insert([
                    'article_id' => $row->id,
                    'category_id' => $row->category_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('article_category');
    }
};
