<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\WPSite;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WPSiteSeeder extends Seeder
{
    public function run(): void
    {
        $companyA = Company::where('slug', 'wayang-group-corporate')->first();

        if (! $companyA) {
            return;
        }

        WPSite::create([
            'company_id'      => $companyA->id,
            'site_name'       => 'Wayang News',
            'site_url'        => 'https://wayang-news.local',
            'wp_username'     => 'wayang_wp_user',
            'wp_app_password' => Str::random(32),
        ]);
    }
}
