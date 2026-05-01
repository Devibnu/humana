<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagerPermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    protected Role $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);

        $this->manager = Role::where('name', 'Manager')->firstOrFail();
    }

    public function test_manager_has_expected_permissions(): void
    {
        $permissions = RolePermission::where('role_id', $this->manager->id)
            ->pluck('menu_key')
            ->toArray();

        $this->assertContains('attendances', $permissions);
        $this->assertContains('leaves', $permissions);
        $this->assertNotContains('payroll.manage', $permissions);
        $this->assertNotContains('employees.destroy', $permissions);
    }

    public function test_manager_cannot_access_payroll_route(): void
    {
        $user = User::factory()->create([
            'role_id' => $this->manager->id,
            'role' => 'manager',
        ]);

        $this->actingAs($user)
            ->get(route('payroll.index'))
            ->assertForbidden();
    }

    public function test_manager_cannot_destroy_employee(): void
    {
        $user = User::factory()->create([
            'role_id' => $this->manager->id,
            'role' => 'manager',
        ]);

        $employee = Employee::create([
            'tenant_id' => Tenant::query()->value('id') ?? Tenant::create([
                'name' => 'Manager Permission Tenant',
                'slug' => 'manager-permission-tenant',
                'domain' => 'manager-permission-tenant.test',
                'status' => 'active',
            ])->id,
            'employee_code' => 'MGR-PERM-001',
            'name' => 'Manager Permission Employee',
            'email' => 'manager-permission-employee@example.test',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->delete(route('employees.destroy', $employee))
            ->assertForbidden();
    }
}