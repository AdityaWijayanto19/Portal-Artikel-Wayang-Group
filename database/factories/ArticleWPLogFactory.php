<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\ArticleWPLog;
use App\Models\WPSite;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArticleWPLogFactory extends Factory
{
    protected $model = ArticleWPLog::class;

    public function definition(): array
    {
        return [
            'article_id' => Article::factory(),
            'wp_site_id' => WPSite::factory(),
            'wp_post_id' => fake()->optional()->numberBetween(100, 9999),
            'status' => fake()->randomElement(['success', 'failed']),
            'response_message' => fake()->sentence(),
            'synced_at' => now(),
        ];
    }
}
