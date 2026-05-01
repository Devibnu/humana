<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeUserLinkTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;

    protected Tenant $tenantB;

    protected Position $positionA;

    protected Department $departmentA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::create([
            'name' => 'Employee Link Tenant A',
            'slug' => 'employee-link-tenant-a',
            'domain' => 'employee-link-tenant-a.test',
            'status' => 'active',
        ]);

        $this->tenantB = Tenant::create([
            'name' => 'Employee Link Tenant B',
            'slug' => 'employee-link-tenant-b',
            'domain' => 'employee-link-tenant-b.test',
            'status' => 'active',
        ]);

        $this->positionA = Position::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Employee Link Position',
            'status' => 'active',
        ]);

        $this->departmentA = Department::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Employee Link Department',
            'status' => 'active',
        ]);
    }

    public function test_admin_hr_can_select_employee_user_id(): void
    {
        $admin = $this->makeUser('admin_hr', $this->tenantA, 'admin-employee-link@example.test', 'Admin Employee Link');
        $employeeUser = $this->makeUser('employee', $this->tenantA, 'selectable-employee-link@example.test', 'Selectable Employee Link');

        $createResponse = $this->actingAs($admin)->get(route('employees.create'));

        $createResponse->assertOk();
        $createResponse->assertSee('data-testid="employee-user-select"', false);
        $createResponse->assertSee($employeeUser->email);

        $this->actingAs($admin)->post(route('employees.store'), [
            'tenant_id' => $this->tenantA->id,
            'user_id' => $employeeUser->id,
            'employee_code' => 'EMP-LINK-1',
            'name' => 'Linked Employee One',
            'email' => 'linked-employee-one@example.test',
            'phone' => '081230010001',
            'position_id' => $this->positionA->id,
            'department_id' => $this->departmentA->id,
            'status' => 'active',
        ])->assertRedirect(route('employees.index'));

        $this->assertDatabaseHas('employees', [
            'employee_code' => 'EMP-LINK-1',
            'user_id' => $employeeUser->id,
        ]);
    }

    public function test_manager_user_dropdown_is_tenant_scoped_and_validation_rejects_other_tenant_user(): void
    {
        $manager = $this->makeUser('manager', $this->tenantA, 'manager-employee-link@example.test', 'Manager Employee Link');
        $tenantEmployeeUser = $this->makeUser('employee', $this->tenantA, 'tenant-employee-link@example.test', 'Tenant Employee Link');
        $otherTenantEmployeeUser = $this->makeUser('employee', $this->tenantB, 'other-tenant-employee-link@example.test', 'Other Tenant Employee Link');

        $response = $this->actingAs($manager)->get(route('employees.create'));

        $response->assertOk();
        $response->assertSee('Tenant Locked');
        $response->assertSee($tenantEmployeeUser->email);
        $response->assertDontSee($otherTenantEmployeeUser->email);

        $this->actingAs($manager)->post(route('employees.store'), [
            'tenant_id' => $this->tenantB->id,
            'user_id' => $otherTenantEmployeeUser->id,
            'employee_code' => 'EMP-LINK-2',
            'name' => 'Manager Linked Employee',
            'email' => 'manager-linked-employee@example.test',
            'phone' => '081230010002',
            'position_id' => $this->positionA->id,
            'department_id' => $this->departmentA->id,
            'status' => 'active',
        ])->assertSessionHasErrors('user_id');

        $this->assertDatabaseMissing('employees', [
            'employee_code' => 'EMP-LINK-2',
        ]);

        $this->actingAs($manager)->post(route('employees.store'), [
            'tenant_id' => $this->tenantB->id,
            'user_id' => $tenantEmployeeUser->id,
            'employee_code' => 'EMP-LINK-3',
            'name' => 'Manager Valid Linked Employee',
            'email' => 'manager-valid-linked-employee@example.test',
            'phone' => '081230010003',
            'position_id' => $this->positionA->id,
            'department_id' => $this->departmentA->id,
            'status' => 'active',
        ])->assertRedirect(route('employees.index'));

        $this->assertDatabaseHas('employees', [
            'employee_code' => 'EMP-LINK-3',
            'tenant_id' => $this->tenantA->id,
            'user_id' => $tenantEmployeeUser->id,
        ]);
    }

    public function test_employee_cannot_access_employee_form(): void
    {
        $employeeUser = $this->makeUser('employee', $this->tenantA, 'employee-form-link@example.test', 'Employee Form Link');

        $this->actingAs($employeeUser)->get(route('employees.create'))->assertForbidden();
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