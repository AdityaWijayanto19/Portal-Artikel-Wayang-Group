<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wp_site_id')->constrained('wp_sites')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->foreignId('sub_category_id')->nullable()->constrained('sub_categories')->nullOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->longText('content');
            $table->string('featured_image_path')->nullable();
            $table->string('image_alt_text')->nullable();
            $table->unsignedInteger('seo_score')->default(0);
            $table->string('yoast_title')->nullable();
            $table->text('yoast_metadesc')->nullable();
            $table->string('yoast_focuskw')->nullable();
            $table->enum('status', ['draft', 'queued', 'published', 'failed'])->default('draft');
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });

        Schema::create('article_wp_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
            $table->foreignId('wp_site_id')->constrained('wp_sites')->cascadeOnDelete();
            $table->unsignedBigInteger('wp_post_id')->nullable();
            $table->enum('status', ['success', 'failed']);
            $table->text('response_message')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_wp_logs');
        Schema::dropIfExists('articles');
    }
};
