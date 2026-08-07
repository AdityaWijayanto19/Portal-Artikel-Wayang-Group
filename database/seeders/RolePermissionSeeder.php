<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Roles (super_admin, admin, author)
        $superAdmin = Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        $admin      = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $author     = Role::create(['name' => 'author', 'guard_name' => 'web']);

        // 2. Buat Permissions
        $viewDashboard   = Permission::create(['name' => 'view dashboard', 'guard_name' => 'web']);
        $manageArticles  = Permission::create(['name' => 'manage articles', 'guard_name' => 'web']);
        $publishArticles = Permission::create(['name' => 'publish articles', 'guard_name' => 'web']);

        // 3. Attach Permissions ke Roles
        $superAdmin->givePermissionTo([$viewDashboard, $manageArticles, $publishArticles]);
        $admin->givePermissionTo([$viewDashboard, $manageArticles]);
        $author->givePermissionTo([$viewDashboard, $manageArticles]);
    }
}
