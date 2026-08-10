<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ActivityLogAccessTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'admin', 'author'] as $role) {
            Role::findOrCreate($role);
        }

        $this->company = Company::create(['name' => 'PT Contoh', 'slug' => 'pt-contoh']);
    }

    private function createUser(string $role): User
    {
        $user = User::create([
            'name' => ucfirst($role),
            'email' => "{$role}@example.com",
            'username' => $role,
            'password' => bcrypt('password'),
            'company_id' => $role === 'super_admin' ? null : $this->company->id,
        ]);
        $user->assignRole($role);

        return $user;
    }

    public function test_super_admin_can_open_activity_log_page(): void
    {
        $this->actingAs($this->createUser('super_admin'))
            ->get(route('activity-logs.index'))
            ->assertOk();
    }

    public function test_admin_can_open_activity_log_page(): void
    {
        $this->actingAs($this->createUser('admin'))
            ->get(route('activity-logs.index'))
            ->assertOk();
    }

    public function test_author_is_forbidden_from_activity_log_page(): void
    {
        $this->actingAs($this->createUser('author'))
            ->get(route('activity-logs.index'))
            ->assertForbidden();
    }

    public function test_author_is_forbidden_from_wp_sync_log_page(): void
    {
        $this->actingAs($this->createUser('author'))
            ->get(route('wp-sync-logs.index'))
            ->assertForbidden();
    }

    public function test_admin_can_open_wp_sync_log_page(): void
    {
        $this->actingAs($this->createUser('admin'))
            ->get(route('wp-sync-logs.index'))
            ->assertOk();
    }

    public function test_dashboard_renders_for_all_roles(): void
    {
        foreach (['super_admin', 'admin', 'author'] as $role) {
            $this->actingAs($this->createUser($role))
                ->get(route('dashboard'))
                ->assertOk();
        }
    }

    public function test_login_writes_activity_log(): void
    {
        $user = $this->createUser('admin');

        $this->post(route('login.store'), [
            'login' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'company_id' => $this->company->id,
            'action' => 'auth.login',
        ]);
    }
}
