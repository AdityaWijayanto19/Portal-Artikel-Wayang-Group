<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        $tables = [
            'role_has_permissions',
            'model_has_permissions',
            'model_has_roles',
            'permissions',
            'roles',
            'article_wp_logs',
            'article_site_publications',
            'article_seo_meta',
            'article_tag',
            'article_category',
            'tags',
            'articles',
            'wp_sites',
            'sub_categories',
            'categories',
            'companies',
            'users',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }
        }

        Schema::enableForeignKeyConstraints();

        $this->call([
            RolePermissionSeeder::class,
            CompanySeeder::class,
            UserSeeder::class,
        ]);
    }
}
