<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Company;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $companyA = Company::where('slug', 'wayang-group-corporate')->first();

        if (! $companyA) {
            return;
        }

        $category = Category::create([
            'company_id' => $companyA->id,
            'name'       => 'Teknologi',
            'slug'       => 'teknologi',
        ]);

        $category->subCategories()->create([
            'name' => 'TKDN',
            'slug' => 'tkdn',
        ]);
    }
}
