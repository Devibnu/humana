<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolesPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_role(): void
    {
        $tenant = $this->makeTenant('roles-create');
        $admin = $this->makeUser('admin_hr', $tenant, 'admin-roles-create@example.test');

        $response = $this->actingAs($admin)->post(route('roles.store'), [
            'name' => 'supervisor',
            'description' => 'Supervisor operasional tenant.',
            'permissions' => ['employees', 'attendances'],
        ]);

        $response->assertRedirect(route('roles.index'));

        $this->assertDatabaseHas('roles', [
            'name' => 'supervisor',
            'description' => 'Supervisor operasional tenant.',
        ]);
    }

    public function test_role_permission_checkboxes_are_saved(): void
    {
        $tenant = $this->makeTenant('roles-permissions');
        $admin = $this->makeUser('admin_hr', $tenant, 'admin-roles-permissions@example.test');

        $response = $this->actingAs($admin)->post(route('roles.store'), [
            'name' => 'auditor',
            'description' => 'Role audit internal.',
            'permissions' => ['reports', 'attendances', 'leaves'],
        ]);

        $response->assertRedirect(route('roles.index'));

        $role = Role::where('name', 'auditor')->firstOrFail();

        $this->assertDatabaseHas('role_permissions', [
            'role_id' => $role->id,
            'menu_key' => 'reports',
            'can_access' => true,
        ]);

        $this->assertDatabaseHas('role_permissions', [
            'role_id' => $role->id,
            'menu_key' => 'attendances',
            'can_access' => true,
        ]);

        $this->assertDatabaseHas('role_permissions', [
            'role_id' => $role->id,
            'menu_key' => 'leaves',
            'can_access' => true,
        ]);

        $this->assertDatabaseMissing('role_permissions', [
            'role_id' => $role->id,
            'menu_key' => 'users',
        ]);
    }

    public function test_user_only_can_access_menus_allowed_by_role_permissions(): void
    {
        $tenant = $this->makeTenant('roles-access');
        $manager = $this->makeUser('manager', $tenant, 'manager-roles-access@example.test');

        $role = Role::where('name', 'Manager')->firstOrFail();

        $manager->update([
            'role_id' => $role->id,
            'role' => $role->system_key,
        ]);

        $role->permissions()->delete();
        $role->permissions()->createMany([
            ['menu_key' => 'attendances', 'can_access' => true],
            ['menu_key' => 'employees', 'can_access' => true],
        ]);

        $dashboardResponse = $this->actingAs($manager)->get(route('dashboard'));

        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee('Absensi');
        $dashboardResponse->assertSee('Karyawan');
        $dashboardResponse->assertDontSee('Cuti / Izin');
        $dashboardResponse->assertDontSee('Pengguna');

        $this->actingAs($manager)->get(route('attendances.index'))->assertOk();
        $this->actingAs($manager)->get(route('employees.index'))->assertOk();
        $this->actingAs($manager)->get(route('leaves.index'))->assertForbidden();
        $this->actingAs($manager)->get(route('users.index'))->assertForbidden();
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

    protected function makeUser(string $role, Tenant $tenant, string $email): User
    {
        return User::create([
            'tenant_id' => $tenant->id,
            'name' => ucfirst($role).' Test User',
            'email' => $email,
            'password' => 'password123',
            'role' => $role,
            'status' => 'active',
        ]);
    }
}