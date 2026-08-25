<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->unsignedInteger('readability_score')->default(0)->after('seo_score');
        });

        Schema::table('article_seo_meta', function (Blueprint $table) {
            $table->renameColumn('content_score', 'readability_score');
        });

        Schema::table('article_seo_meta', function (Blueprint $table) {
            $table->index('yoast_focuskw');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('readability_score');
        });

        Schema::table('article_seo_meta', function (Blueprint $table) {
            $table->renameColumn('readability_score', 'content_score');
            $table->dropIndex(['yoast_focuskw']);
        });
    }
};
