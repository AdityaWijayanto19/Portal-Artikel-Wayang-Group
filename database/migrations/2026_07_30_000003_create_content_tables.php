<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->timestamps();

            $table->unique(['company_id', 'slug']);
        });

        Schema::create('wp_sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('site_name');
            $table->string('site_url');
            $table->string('wp_username');
            $table->text('wp_app_password');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wp_sites');
        Schema::dropIfExists('categories');
    }
};
