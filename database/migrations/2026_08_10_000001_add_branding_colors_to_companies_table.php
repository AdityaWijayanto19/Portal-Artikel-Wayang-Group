<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('primary_color', 7)
                ->nullable()
                ->default('#C59B27')
                ->after('logo_path');
            $table->string('sidebar_color', 7)
                ->nullable()
                ->default('#1E1E1E')
                ->after('primary_color');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['primary_color', 'sidebar_color']);
        });
    }
};