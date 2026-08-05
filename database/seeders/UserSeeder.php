<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ambil Company
        $companyA = Company::where('slug', 'wayang-group-corporate')->first();
        $companyB = Company::where('slug', 'wayang-group-media')->first();

        // 2. Buat Super Admin (company_id null)
        $superAdminUser = User::factory()->create([
            'name'       => 'Super Admin',
            'email'      => 'superadmin@wayang.test',
            'username'   => 'superadmin',
            'company_id' => null,
        ]);

        // 3. Buat Admin & Author (terhubung langsung via company_id)
        $adminUser = User::factory()->create([
            'name'       => 'Admin Holding',
            'email'      => 'admin@wayang.test',
            'username'   => 'adminholding',
            'company_id' => $companyA?->id,
        ]);

        $authorUser = User::factory()->create([
            'name'       => 'Author Holding',
            'email'      => 'author@wayang.test',
            'username'   => 'authorholding',
            'company_id' => $companyA?->id,
        ]);

        // 4. Assign Roles
        $superAdminUser->assignRole('super_admin');
        $adminUser->assignRole('admin');
        $authorUser->assignRole('author');
    }
}
