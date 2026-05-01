<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeesUiTenantBadgeTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'UI Tenant',
            'slug' => 'ui-tenant',
            'domain' => 'ui-tenant.test',
            'status' => 'active',
        ]);

        Position::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'UI Position',
            'status' => 'active',
        ]);

        Department::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'UI Department',
            'status' => 'active',
        ]);
    }

    public function test_manager_create_and_edit_forms_show_tenant_locked_badge(): void
    {
        $manager = $this->makeUser('manager', 'manager-badge@example.test', 'Manager Badge');
        $employee = Employee::create([
            'tenant_id' => $this->tenant->id,
            'employee_code' => 'EMP-UI-1',
            'name' => 'Employee UI Badge',
            'email' => 'employee-ui-badge@example.test',
            'status' => 'active',
        ]);

        $createResponse = $this->actingAs($manager)->get(route('employees.create'));
        $createResponse->assertOk();
        $createResponse->assertSee('Tenant Locked');
        $createResponse->assertSee('Manager dibatasi ke tenant sendiri.');
        $createResponse->assertDontSee('data-testid="tenant-select"', false);

        $editResponse = $this->actingAs($manager)->get(route('employees.edit', $employee));
        $editResponse->assertOk();
        $editResponse->assertSee('Tenant Locked');
        $editResponse->assertDontSee('data-testid="tenant-select"', false);
    }

    public function test_admin_hr_create_and_edit_forms_show_tenant_dropdown(): void
    {
        $admin = $this->makeUser('admin_hr', 'admin-badge@example.test', 'Admin Badge');
        $employee = Employee::create([
            'tenant_id' => $this->tenant->id,
            'employee_code' => 'EMP-UI-2',
            'name' => 'Employee UI Admin',
            'email' => 'employee-ui-admin@example.test',
            'status' => 'active',
        ]);

        $createResponse = $this->actingAs($admin)->get(route('employees.create'));
        $createResponse->assertOk();
        $createResponse->assertSee('data-testid="tenant-select"', false);
        $createResponse->assertDontSee('Tenant Locked');

        $editResponse = $this->actingAs($admin)->get(route('employees.edit', $employee));
        $editResponse->assertOk();
        $editResponse->assertSee('data-testid="tenant-select"', false);
        $editResponse->assertDontSee('Tenant Locked');
    }

    protected function makeUser(string $role, string $email, string $name): User
    {
        return User::create([
            'tenant_id' => $this->tenant->id,
            'name' => $name,
            'email' => $email,
            'password' => 'password',
            'role' => $role,
            'status' => 'active',
        ]);
    }
}