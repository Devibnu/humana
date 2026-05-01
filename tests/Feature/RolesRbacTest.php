<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RolesRbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
    }

    public function test_admin_hr_with_role_id_can_access_all_management_menus_even_if_legacy_role_string_is_wrong(): void
    {
        $tenant = $this->makeTenant('roles-rbac-admin');
        $admin = $this->makeUserFromRoleId('Admin HR', $tenant, 'roles-rbac-admin@example.test');

        DB::table('users')->where('id', $admin->id)->update(['role' => 'employee']);
        $admin->refresh();

        $this->actingAs($admin)->get(route('users.index'))->assertOk();
        $this->actingAs($admin)->get(route('tenants.index'))->assertOk();
        $this->actingAs($admin)->get(route('roles.index'))->assertOk();

        $dashboardResponse = $this->actingAs($admin)->get(route('dashboard'));

        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee('Pengguna');
        $dashboardResponse->assertSee('Tenant');
        $dashboardResponse->assertSee('Role');
        $dashboardResponse->assertSee('Absensi');
        $dashboardResponse->assertSee('Cuti / Izin');
    }

    public function test_manager_with_role_id_remains_limited_to_manager_access_even_if_legacy_role_string_is_wrong(): void
    {
        $tenant = $this->makeTenant('roles-rbac-manager');
        $manager = $this->makeUserFromRoleId('Manager', $tenant, 'roles-rbac-manager@example.test');

        DB::table('users')->where('id', $manager->id)->update(['role' => 'admin_hr']);
        $manager->refresh();

        $this->actingAs($manager)->get(route('users.index'))->assertOk();
        $this->actingAs($manager)->get(route('employees.index'))->assertOk();
        $this->actingAs($manager)->get(route('tenants.index'))->assertForbidden();
        $this->actingAs($manager)->get(route('roles.index'))->assertForbidden();

        $dashboardResponse = $this->actingAs($manager)->get(route('dashboard'));

        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee('Pengguna');
        $dashboardResponse->assertSee('Karyawan');
        $dashboardResponse->assertSee('Absensi');
        $dashboardResponse->assertDontSee('Tenant');
        $dashboardResponse->assertDontSee('Role');
        $dashboardResponse->assertDontSee('Payroll');
    }

    public function test_employee_with_role_id_remains_limited_to_employee_access_even_if_legacy_role_string_is_wrong(): void
    {
        $tenant = $this->makeTenant('roles-rbac-employee');
        $employee = $this->makeUserFromRoleId('Employee', $tenant, 'roles-rbac-employee@example.test');

        DB::table('users')->where('id', $employee->id)->update(['role' => 'admin_hr']);
        $employee->refresh();

        $this->actingAs($employee)->get(route('profile'))->assertOk();
        $this->actingAs($employee)->get(route('user-profile.index'))->assertOk();
        $this->actingAs($employee)->get(route('attendances.index'))->assertForbidden();
        $this->actingAs($employee)->get(route('users.index'))->assertForbidden();
        $this->actingAs($employee)->get(route('employees.index'))->assertForbidden();

        $dashboardResponse = $this->actingAs($employee)->get(route('dashboard'));

        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee('Profil Saya');
        $dashboardResponse->assertDontSee('Absensi');
        $dashboardResponse->assertDontSee('Cuti / Izin');
        $dashboardResponse->assertDontSee('Data Master');
        $dashboardResponse->assertDontSee('Pengguna');
        $dashboardResponse->assertDontSee('Tenant');
    }

    protected function makeTenant(string $slug): Tenant
    {
        return Tenant::create([
            'name' => ucfirst(str_replace('-', ' ', $slug)).' Tenant',
            'slug' => $slug,
            'domain' => $slug.'.test',
            'status' => 'active',
        ]);
    }

    protected function makeUserFromRoleId(string $roleName, Tenant $tenant, string $email): User
    {
        $role = Role::where('name', $roleName)->firstOrFail();

        return User::create([
            'tenant_id' => $tenant->id,
            'name' => $roleName.' User',
            'email' => $email,
            'password' => 'password123',
            'role_id' => $role->id,
            'status' => 'active',
        ]);
    }
}