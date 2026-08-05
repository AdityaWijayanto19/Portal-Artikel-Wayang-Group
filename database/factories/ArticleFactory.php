<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\Category;
use App\Models\Company;
use App\Models\SubCategory;
use App\Models\User;
use App\Models\WPSite;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        $title = fake()->sentence(8);
        $focusKeyword = fake()->unique()->words(2, true);
        $content = fake()->paragraphs(8, true)."\n\n".fake()->paragraphs(8, true);

        return [
            'company_id' => Company::factory(),
            'user_id' => User::factory(),
            'wp_site_id' => WPSite::factory(),
            'category_id' => Category::factory(),
            'sub_category_id' => SubCategory::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'content' => $content,
            'featured_image_path' => null,
            'image_alt_text' => fake()->sentence(4),
            'seo_score' => fake()->numberBetween(35, 95),
            'yoast_title' => $title,
            'yoast_metadesc' => fake()->text(140),
            'yoast_focuskw' => $focusKeyword,
            'status' => fake()->randomElement(['draft', 'queued', 'published', 'failed']),
        ];
    }
}
