<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Category;
use App\Models\Company;
use App\Models\SubCategory;
use App\Models\User;
use App\Models\WPSite;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $companyA    = Company::where('slug', 'wayang-group-corporate')->first();
        $authorUser  = User::where('username', 'authorholding')->first();
        $site        = WPSite::where('site_name', 'Wayang News')->first();
        $category    = Category::where('slug', 'teknologi')->first();
        $subCategory = SubCategory::where('slug', 'tkdn')->first();

        if (! $companyA || ! $authorUser || ! $site || ! $category) {
            return;
        }

        Article::factory()->create([
            'company_id'      => $companyA->id,
            'user_id'         => $authorUser->id,
            'wp_site_id'      => $site->id,
            'category_id'     => $category->id,
            'sub_category_id' => $subCategory?->id,
            'title'           => 'Mengenal Strategi Artikel SEO Terpusat',
            'slug'            => 'mengenal-strategi-artikel-seo-terpusat',
            'content'         => str_repeat("Artikel ini membahas strategi SEO terpusat untuk holding dan anak perusahaan. ", 40),
            'seo_score'       => 84,
            'readability_score' => 78,
            'yoast_title'     => 'Strategi Artikel SEO Terpusat',
            'yoast_metadesc'  => 'Panduan terpusat untuk mengelola artikel SEO lintas holding dengan kualitas terukur.',
            'yoast_focuskw'   => 'artikel seo terpusat',
            'status'          => 'draft',
        ]);
    }
}
