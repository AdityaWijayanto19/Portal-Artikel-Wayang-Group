<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\WPSite;
use Illuminate\Database\Eloquent\Factories\Factory;

class WPSiteFactory extends Factory
{
    protected $model = WPSite::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'site_name' => fake()->company().' WP',
            'site_url' => 'https://'.fake()->unique()->domainName(),
            'wp_username' => fake()->userName(),
            'wp_app_password' => fake()->password(20, 40),
        ];
    }
}
