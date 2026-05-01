<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeesRbacTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;

    protected Tenant $tenantB;

    protected Position $positionA;

    protected Position $positionB;

    protected Department $departmentA;

    protected Department $departmentB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::create([
            'name' => 'Employees Tenant A',
            'slug' => 'employees-tenant-a',
            'domain' => 'employees-tenant-a.test',
            'status' => 'active',
        ]);

        $this->tenantB = Tenant::create([
            'name' => 'Employees Tenant B',
            'slug' => 'employees-tenant-b',
            'domain' => 'employees-tenant-b.test',
            'status' => 'active',
        ]);

        $this->positionA = Position::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Supervisor',
            'status' => 'active',
        ]);

        $this->positionB = Position::create([
            'tenant_id' => $this->tenantB->id,
            'name' => 'Director',
            'status' => 'active',
        ]);

        $this->departmentA = Department::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Operations',
            'status' => 'active',
        ]);

        $this->departmentB = Department::create([
            'tenant_id' => $this->tenantB->id,
            'name' => 'Finance',
            'status' => 'active',
        ]);
    }

    public function test_admin_hr_has_full_employee_crud(): void
    {
        $admin = $this->makeUser('admin_hr', $this->tenantA, 'employees-admin@example.test');

        $this->actingAs($admin)->get(route('employees.index'))->assertOk();
        $this->actingAs($admin)->get(route('employees.create'))->assertOk();

        $storeResponse = $this->actingAs($admin)->post(route('employees.store'), [
            'tenant_id' => $this->tenantA->id,
            'employee_code' => 'EMP-CRUD-1',
            'name' => 'Employee Crud',
            'email' => 'employee-crud@example.test',
            'phone' => '08123',
            'position_id' => $this->positionA->id,
            'department_id' => $this->departmentA->id,
            'status' => 'active',
        ]);

        $storeResponse->assertRedirect(route('employees.index'));

        $employee = Employee::where('employee_code', 'EMP-CRUD-1')->firstOrFail();

        $this->actingAs($admin)->get(route('employees.edit', $employee))->assertOk();

        $this->actingAs($admin)->put(route('employees.update', $employee), [
            'tenant_id' => $this->tenantA->id,
            'employee_code' => 'EMP-CRUD-1',
            'name' => 'Employee Crud Updated',
            'email' => 'employee-crud-updated@example.test',
            'phone' => '08124',
            'position_id' => $this->positionA->id,
            'department_id' => $this->departmentA->id,
            'status' => 'inactive',
        ])->assertRedirect(route('employees.index'));

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'name' => 'Employee Crud Updated',
            'status' => 'inactive',
        ]);

        $this->actingAs($admin)->delete(route('employees.destroy', $employee))->assertRedirect(route('employees.index'));
        $this->assertDatabaseMissing('employees', ['id' => $employee->id]);
    }

    public function test_manager_can_store_and_update_employee_only_within_own_tenant_and_cannot_destroy(): void
    {
        $manager = $this->makeUser('manager', $this->tenantA, 'employees-manager@example.test');

        $employeeA = Employee::create([
            'tenant_id' => $this->tenantA->id,
            'employee_code' => 'EMP-A-1',
            'name' => 'Tenant A Employee',
            'email' => 'tenant-a-employee@example.test',
            'status' => 'active',
        ]);

        $employeeB = Employee::create([
            'tenant_id' => $this->tenantB->id,
            'employee_code' => 'EMP-B-1',
            'name' => 'Tenant B Employee',
            'email' => 'tenant-b-employee@example.test',
            'status' => 'active',
        ]);

        $response = $this->actingAs($manager)->get(route('employees.index', ['tenant_id' => $this->tenantB->id]));

        $response->assertOk();
        $response->assertSee($employeeA->name);
        $response->assertDontSee($employeeB->name);

        $this->actingAs($manager)->get(route('employees.create'))->assertOk();

        $this->actingAs($manager)->post(route('employees.store'), [
            'tenant_id' => $this->tenantB->id,
            'employee_code' => 'EMP-MGR-1',
            'name' => 'Manager Created Employee',
            'email' => 'manager-created@example.test',
            'phone' => '08125',
            'position_id' => $this->positionA->id,
            'department_id' => $this->departmentA->id,
            'status' => 'active',
        ])->assertRedirect(route('employees.index'));

        $this->assertDatabaseHas('employees', [
            'employee_code' => 'EMP-MGR-1',
            'tenant_id' => $this->tenantA->id,
            'name' => 'Manager Created Employee',
        ]);

        $this->actingAs($manager)->get(route('employees.edit', $employeeA))->assertOk();
        $this->actingAs($manager)->get(route('employees.edit', $employeeB))->assertForbidden();

        $this->actingAs($manager)->put(route('employees.update', $employeeA), [
            'tenant_id' => $this->tenantB->id,
            'employee_code' => 'EMP-A-1',
            'name' => 'Manager Updated Employee',
            'email' => 'tenant-a-employee-updated@example.test',
            'phone' => '08126',
            'position_id' => $this->positionA->id,
            'department_id' => $this->departmentA->id,
            'status' => 'active',
        ])->assertRedirect(route('employees.index'));

        $this->assertDatabaseHas('employees', [
            'id' => $employeeA->id,
            'tenant_id' => $this->tenantA->id,
            'name' => 'Manager Updated Employee',
            'email' => 'tenant-a-employee-updated@example.test',
        ]);

        $this->actingAs($manager)->put(route('employees.update', $employeeB), [
            'tenant_id' => $this->tenantA->id,
            'employee_code' => 'EMP-B-1',
            'name' => 'Cross Tenant Update',
            'email' => 'cross-tenant-update@example.test',
            'position_id' => $this->positionA->id,
            'department_id' => $this->departmentA->id,
            'status' => 'active',
        ])->assertForbidden();

        $this->actingAs($manager)->delete(route('employees.destroy', $employeeA))->assertForbidden();

        $this->assertDatabaseHas('employees', [
            'id' => $employeeA->id,
            'name' => 'Manager Updated Employee',
        ]);
    }

    public function test_employee_cannot_crud_employees_and_can_only_view_own_profile(): void
    {
        $employeeUser = $this->makeUser('employee', $this->tenantA, 'employees-employee@example.test');

        $employee = Employee::create([
            'tenant_id' => $this->tenantA->id,
            'employee_code' => 'EMP-E-1',
            'name' => 'Employee Record',
            'email' => 'employee-record@example.test',
            'status' => 'active',
        ]);

        $this->actingAs($employeeUser)->get(route('employees.index'))->assertForbidden();
        $this->actingAs($employeeUser)->get(route('employees.create'))->assertForbidden();
        $this->actingAs($employeeUser)->post(route('employees.store'), [
            'tenant_id' => $this->tenantA->id,
            'employee_code' => 'EMP-E-2',
            'name' => 'Employee Create Attempt',
            'email' => 'employee-create-attempt@example.test',
            'status' => 'active',
        ])->assertForbidden();
        $this->actingAs($employeeUser)->get(route('employees.edit', $employee))->assertForbidden();
        $this->actingAs($employeeUser)->put(route('employees.update', $employee), [
            'tenant_id' => $this->tenantA->id,
            'employee_code' => 'EMP-E-1',
            'name' => 'Employee Update Attempt',
            'email' => 'employee-update-attempt@example.test',
            'status' => 'active',
        ])->assertForbidden();
        $this->actingAs($employeeUser)->delete(route('employees.destroy', $employee))->assertForbidden();
        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'name' => 'Employee Record',
        ]);
        $this->actingAs($employeeUser)->get('/user-profile')->assertOk();
    }

    protected function makeUser(string $role, Tenant $tenant, string $email): User
    {
        return User::create([
            'tenant_id' => $tenant->id,
            'name' => ucfirst($role).' Employee User',
            'email' => $email,
            'password' => 'password',
            'role' => $role,
            'status' => 'active',
        ]);
    }
}