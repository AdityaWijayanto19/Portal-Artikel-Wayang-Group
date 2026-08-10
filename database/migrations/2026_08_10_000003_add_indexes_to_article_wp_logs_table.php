<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('article_wp_logs', function (Blueprint $table) {
            $table->index(['status', 'synced_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('article_wp_logs', function (Blueprint $table) {
            $table->dropIndex(['status', 'synced_at']);
            $table->dropIndex(['created_at']);
        });
    }
};
