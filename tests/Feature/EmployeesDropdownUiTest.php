<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeesDropdownUiTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantOne;

    protected Tenant $tenantTwo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantOne = Tenant::create([
            'name' => 'Tenant One',
            'slug' => 'tenant-one',
            'domain' => 'tenant-one.test',
            'status' => 'active',
        ]);

        $this->tenantTwo = Tenant::create([
            'name' => 'Tenant Two',
            'slug' => 'tenant-two',
            'domain' => 'tenant-two.test',
            'status' => 'active',
        ]);

        Position::create([
            'tenant_id' => $this->tenantOne->id,
            'name' => 'HR Manager',
            'status' => 'active',
        ]);

        Position::create([
            'tenant_id' => $this->tenantOne->id,
            'name' => 'Recruiter',
            'status' => 'active',
        ]);

        Position::create([
            'tenant_id' => $this->tenantTwo->id,
            'name' => 'Tenant Two Lead',
            'status' => 'active',
        ]);

        Department::create([
            'tenant_id' => $this->tenantOne->id,
            'name' => 'Human Resources',
            'status' => 'active',
        ]);

        Department::create([
            'tenant_id' => $this->tenantOne->id,
            'name' => 'Operations',
            'status' => 'active',
        ]);

        Department::create([
            'tenant_id' => $this->tenantTwo->id,
            'name' => 'Tenant Two Department',
            'status' => 'active',
        ]);
    }

    public function test_admin_hr_create_form_shows_all_positions_and_departments_for_admin_tenant(): void
    {
        $admin = $this->makeUser('admin_hr', $this->tenantOne, 'admin-dropdown@example.test', 'Admin Dropdown');

        $response = $this->actingAs($admin)->get(route('employees.create'));

        $response->assertOk();
        $response->assertSee('HR Manager');
        $response->assertSee('Recruiter');
        $response->assertSee('Human Resources');
        $response->assertSee('Operations');
        $response->assertDontSee('Tenant Two Lead');
        $response->assertDontSee('Tenant Two Department');
        $response->assertSee('data-testid="tenant-select"', false);
        $response->assertDontSee('Tenant Locked');
    }

    public function test_manager_create_form_only_shows_positions_and_departments_for_manager_tenant(): void
    {
        $manager = $this->makeUser('manager', $this->tenantTwo, 'manager-dropdown@example.test', 'Manager Dropdown');

        $response = $this->actingAs($manager)->get(route('employees.create'));

        $response->assertOk();
        $response->assertSee('Tenant Two Lead');
        $response->assertSee('Tenant Two Department');
        $response->assertDontSee('HR Manager');
        $response->assertDontSee('Recruiter');
        $response->assertDontSee('Human Resources');
        $response->assertDontSee('Operations');
        $response->assertSee('Tenant Locked');
        $response->assertSee('Manager dibatasi ke tenant sendiri.');
    }

    public function test_employee_cannot_access_employee_create_form(): void
    {
        $employeeUser = $this->makeUser('employee', $this->tenantOne, 'employee-dropdown@example.test', 'Employee Dropdown');

        $this->actingAs($employeeUser)
            ->get(route('employees.create'))
            ->assertForbidden();
    }

    protected function makeUser(string $role, Tenant $tenant, string $email, string $name): User
    {
        return User::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'email' => $email,
            'password' => 'password',
            'role' => $role,
            'status' => 'active',
        ]);
    }
}