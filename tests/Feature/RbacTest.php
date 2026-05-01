<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;

    protected Tenant $tenantB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);

        $this->tenantA = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
            'domain' => 'tenant-a.test',
            'status' => 'active',
        ]);

        $this->tenantB = Tenant::create([
            'name' => 'Tenant B',
            'slug' => 'tenant-b',
            'domain' => 'tenant-b.test',
            'status' => 'active',
        ]);
    }

    public function test_admin_hr_can_access_user_and_tenant_crud_pages(): void
    {
        $admin = $this->makeUser('admin_hr', $this->tenantA, 'admin@example.test');

        $this->actingAs($admin)->get('/users')->assertOk();
        $this->actingAs($admin)->get('/tenants')->assertOk();
        $this->actingAs($admin)->get('/tenants/create')->assertOk();
    }

    public function test_manager_can_only_view_users_within_own_tenant(): void
    {
        $manager = $this->makeUser('manager', $this->tenantA, 'manager@example.test');
        $ownTenantUser = $this->makeUser('employee', $this->tenantA, 'tenant-a-user@example.test', 'Tenant A User');
        $otherTenantUser = $this->makeUser('employee', $this->tenantB, 'tenant-b-user@example.test', 'Tenant B User');

        $response = $this->actingAs($manager)->get('/users?tenant_id='.$this->tenantB->id);

        $response->assertOk();
        $response->assertSee($ownTenantUser->name);
        $response->assertDontSee($otherTenantUser->name);
        $response->assertSee('Data Master');
        $response->assertSee('Pengguna');
        $response->assertDontSee('href="'.route('tenants.index').'"', false);
    }

    public function test_employee_can_only_access_own_profile_and_sees_no_admin_menus(): void
    {
        $employee = $this->makeUser('employee', $this->tenantA, 'employee@example.test');

        $this->actingAs($employee)->get('/user-profile')->assertOk();
        $this->actingAs($employee)->get('/users')->assertForbidden();
        $this->actingAs($employee)->get('/tenants')->assertForbidden();

        $dashboardResponse = $this->actingAs($employee)->get('/dashboard');

        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee('Profil Saya');
        $dashboardResponse->assertDontSee('href="'.route('attendances.index').'"', false);
        $dashboardResponse->assertDontSee('href="'.route('leaves.index').'"', false);
        $dashboardResponse->assertDontSee('Data Master');
        $dashboardResponse->assertDontSee('Pengguna');
        $dashboardResponse->assertDontSee('Tenant');
    }

    public function test_admin_sidebar_shows_all_management_menus(): void
    {
        $admin = $this->makeUser('admin_hr', $this->tenantA, 'admin-sidebar@example.test');

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Data Master');
        $response->assertSee('Pengguna');
        $response->assertSee('Tenant');
    }

    protected function makeUser(string $role, Tenant $tenant, string $email, ?string $name = null): User
    {
        return User::create([
            'tenant_id' => $tenant->id,
            'name' => $name ?? ucfirst($role).' User',
            'email' => $email,
            'password' => 'password',
            'role' => $role,
            'status' => 'active',
        ]);
    }
}