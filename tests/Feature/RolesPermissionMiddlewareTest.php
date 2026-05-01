<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkLocation;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolesPermissionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);

        $this->tenant = Tenant::create([
            'name' => 'Permission Test Tenant',
            'slug' => 'permission-test-tenant',
            'domain' => 'permission-test.test',
            'status' => 'active',
        ]);

        Department::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Permission Test Dept',
            'status' => 'active',
        ]);

        Position::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Permission Test Position',
            'status' => 'active',
        ]);

        WorkLocation::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Permission Test Office',
            'address' => 'Jakarta',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'radius' => 250,
        ]);
    }

    public function test_admin_hr_can_access_all_permission_protected_routes(): void
    {
        $admin = $this->makeUser('admin_hr', 'admin-perm@example.test');

        // Master data (admin-only keys)
        $this->actingAs($admin)->get(route('departments.index'))->assertOk();
        $this->actingAs($admin)->get(route('positions.index'))->assertOk();
        $this->actingAs($admin)->get(route('tenants.index'))->assertOk();
        $this->actingAs($admin)->get(route('roles.index'))->assertOk();

        // Shared master (admin + manager)
        $this->actingAs($admin)->get(route('employees.index'))->assertOk();
        $this->actingAs($admin)->get(route('work_locations.index'))->assertOk();
        $this->actingAs($admin)->get(route('users.index'))->assertOk();

        // Action-level: attendances.manage + leaves.manage (admin + manager)
        $this->actingAs($admin)->get(route('attendances.create'))->assertOk();
        $this->actingAs($admin)->get(route('attendances.analytics'))->assertOk();
        $this->actingAs($admin)->get(route('leaves.create'))->assertOk();
        $this->actingAs($admin)->get(route('leaves.export'))->assertOk();
        $this->actingAs($admin)->get(route('leaves.analytics'))->assertOk();

        // Action-level: users.manage (admin only)
        $this->actingAs($admin)->get(route('users.create'))->assertOk();
    }

    public function test_manager_can_access_permitted_routes_but_blocked_from_admin_routes(): void
    {
        $manager = $this->makeUser('manager', 'manager-perm@example.test');
        $employee = $this->makeEmployee('manager-locked-employee@example.test');

        // Manager has permission for these
        $this->actingAs($manager)->get(route('employees.index'))->assertOk();
        $this->actingAs($manager)->get(route('work_locations.index'))->assertOk();
        $this->actingAs($manager)->get(route('users.index'))->assertOk();
        $this->actingAs($manager)->get(route('attendances.index'))->assertOk();
        $this->actingAs($manager)->get(route('attendances.create'))->assertOk();
        $this->actingAs($manager)->get(route('leaves.index'))->assertOk();
        $this->actingAs($manager)->get(route('leaves.export'))->assertOk();
        $this->actingAs($manager)->get(route('leaves.analytics'))->assertOk();

        // Manager is blocked from admin-only keys
        $this->actingAs($manager)->get(route('departments.index'))->assertForbidden();
        $this->actingAs($manager)->get(route('positions.index'))->assertForbidden();
        $this->actingAs($manager)->get(route('tenants.index'))->assertForbidden();
        $this->actingAs($manager)->get(route('roles.index'))->assertForbidden();
        $this->actingAs($manager)->delete(route('employees.destroy', $employee))->assertForbidden();
        $this->actingAs($manager)->get(route('leaves.create'))->assertForbidden();

        // Manager is blocked from users.manage (can view list, not CRUD)
        $this->actingAs($manager)->get(route('users.create'))->assertForbidden();
    }

    public function test_employee_is_limited_to_profile_only_access(): void
    {
        $employee = $this->makeUser('employee', 'employee-perm@example.test');

        $this->actingAs($employee)->get(route('profile'))->assertOk();
        $this->assertTrue($employee->hasMenuAccess('profile'));

        $this->actingAs($employee)->get(route('attendances.create'))->assertForbidden();
        $this->actingAs($employee)->get(route('attendances.analytics'))->assertForbidden();
        $this->actingAs($employee)->get(route('attendances.index'))->assertForbidden();
        $this->actingAs($employee)->get(route('leaves.index'))->assertForbidden();
        $this->actingAs($employee)->get(route('leaves.create'))->assertForbidden();
        $this->actingAs($employee)->get(route('leaves.export'))->assertForbidden();
        $this->actingAs($employee)->get(route('leaves.analytics'))->assertForbidden();
        $this->assertFalse($employee->hasMenuAccess('payroll'));
        $this->assertFalse($employee->hasMenuAccess('payroll.manage'));

        $this->actingAs($employee)->get(route('employees.index'))->assertForbidden();
        $this->actingAs($employee)->get(route('users.index'))->assertForbidden();
        $this->actingAs($employee)->get(route('departments.index'))->assertForbidden();
        $this->actingAs($employee)->get(route('positions.index'))->assertForbidden();
        $this->actingAs($employee)->get(route('tenants.index'))->assertForbidden();
        $this->actingAs($employee)->get(route('roles.index'))->assertForbidden();
        $this->actingAs($employee)->get(route('payroll.index'))->assertForbidden();
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get(route('departments.index'))->assertRedirect(route('login'));
        $this->get(route('attendances.index'))->assertRedirect(route('login'));
        $this->get(route('employees.index'))->assertRedirect(route('login'));
    }

    protected function makeUser(string $role, string $email): User
    {
        return User::create([
            'tenant_id' => $this->tenant->id,
            'name' => ucfirst($role).' Permission User',
            'email' => $email,
            'password' => 'password',
            'role' => $role,
            'status' => 'active',
        ]);
    }

    protected function makeEmployee(string $email): Employee
    {
        return Employee::create([
            'tenant_id' => $this->tenant->id,
            'work_location_id' => WorkLocation::query()->value('id'),
            'employee_code' => 'PERM-EMP-001',
            'name' => 'Permission Employee',
            'email' => $email,
            'status' => 'active',
        ]);
    }
}
