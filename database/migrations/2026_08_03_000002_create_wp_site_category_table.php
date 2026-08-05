<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wp_site_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wp_site_id')->constrained('wp_sites')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['wp_site_id', 'category_id']);
        });

        if (Schema::hasColumn('wp_sites', 'category_id')) {
            $rows = DB::table('wp_sites')
                ->whereNotNull('category_id')
                ->select('id', 'category_id')
                ->get();

            foreach ($rows as $row) {
                DB::table('wp_site_category')->insert([
                    'wp_site_id' => $row->id,
                    'category_id' => $row->category_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wp_site_category');
    }
};