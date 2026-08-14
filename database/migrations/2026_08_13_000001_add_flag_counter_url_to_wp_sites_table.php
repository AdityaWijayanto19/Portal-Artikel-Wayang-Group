<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wp_sites', function (Blueprint $table) {
            $table->text('flag_counter_url')->nullable()->after('wp_app_password');
        });
    }

    public function down(): void
    {
        Schema::table('wp_sites', function (Blueprint $table) {
            $table->dropColumn('flag_counter_url');
        });
    }
};
