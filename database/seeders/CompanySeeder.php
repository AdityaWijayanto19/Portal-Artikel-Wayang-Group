<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        Company::create([
            'name' => 'Wayang Group Corporate',
            'slug' => 'wayang-group-corporate',
        ]);

        Company::create([
            'name' => 'Wayang Group Media',
            'slug' => 'wayang-group-media',
        ]);
    }
}
