<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveMonthYearFilterTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_hr_table_respects_selected_month_year_scope(): void
    {
        [$tenantA, $tenantB, $employeeA, $employeeB, $admin] = $this->seedTableScopeData();

        $response = $this->actingAs($admin)->get(route('leaves.index', [
            'month' => 4,
            'year' => 2026,
        ]));

        $response->assertOk();
        $response->assertSee('Admin April Match');
        $response->assertSee('Other Tenant April Match');
        $response->assertDontSee('Admin March Outside');
        $response->assertDontSee('Admin Previous Year Outside');
    }

    public function test_manager_table_filter_remains_tenant_scoped(): void
    {
        [$tenantA, $tenantB, $employeeA, $employeeB, $admin] = $this->seedTableScopeData();
        $manager = $this->makeUser('manager', $tenantA, 'leave-table-filter-manager@example.test', 'Leave Table Filter Manager');

        $response = $this->actingAs($manager)->get(route('leaves.index', [
            'month' => 4,
            'year' => 2026,
        ]));

        $response->assertOk();
        $response->assertSee('Admin April Match');
        $response->assertDontSee('Other Tenant April Match');
        $response->assertDontSee('Admin March Outside');
    }

    public function test_employee_table_filter_only_shows_own_matching_records(): void
    {
        $tenant = $this->makeTenant('leave-table-filter-employee-tenant');
        $employeeUser = $this->makeUser('employee', $tenant, 'leave-table-filter-employee@example.test', 'Leave Table Filter Employee');
        $otherUser = $this->makeUser('employee', $tenant, 'leave-table-filter-other@example.test', 'Leave Table Filter Other');
        $employee = $this->makeEmployee($tenant, 'LVE-TBL-E1', 'Leave Table Filter Employee A', 'leave-table-filter-employee-a@example.test', $employeeUser);
        $otherEmployee = $this->makeEmployee($tenant, 'LVE-TBL-E2', 'Leave Table Filter Employee B', 'leave-table-filter-employee-b@example.test', $otherUser);

        $this->makeLeave($tenant, $employee, '2026-04-01', '2026-04-02', 'pending', 'Employee April Match');
        $this->makeLeave($tenant, $employee, '2026-05-01', '2026-05-01', 'approved', 'Employee May Outside');
        $this->makeLeave($tenant, $otherEmployee, '2026-04-04', '2026-04-05', 'rejected', 'Other Employee April Hidden');

        $response = $this->actingAs($employeeUser)->get(route('leaves.index', [
            'month' => 4,
            'year' => 2026,
            'employee_id' => $otherEmployee->id,
        ]));

        $response->assertOk();
        $response->assertSee('Employee April Match');
        $response->assertDontSee('Employee May Outside');
        $response->assertDontSee('Other Employee April Hidden');
    }

    protected function seedTableScopeData(): array
    {
        $tenantA = $this->makeTenant('leave-table-filter-tenant-a');
        $tenantB = $this->makeTenant('leave-table-filter-tenant-b');
        $employeeA = $this->makeEmployee($tenantA, 'LVE-TBL-A1', 'Leave Table Filter Employee A', 'leave-table-filter-a@example.test');
        $employeeB = $this->makeEmployee($tenantB, 'LVE-TBL-B1', 'Leave Table Filter Employee B', 'leave-table-filter-b@example.test');
        $admin = $this->makeUser('admin_hr', $tenantA, 'leave-table-filter-admin@example.test', 'Leave Table Filter Admin');

        $this->makeLeave($tenantA, $employeeA, '2026-04-02', '2026-04-03', 'pending', 'Admin April Match');
        $this->makeLeave($tenantA, $employeeA, '2026-03-10', '2026-03-11', 'approved', 'Admin March Outside');
        $this->makeLeave($tenantA, $employeeA, '2025-04-15', '2025-04-15', 'rejected', 'Admin Previous Year Outside');
        $this->makeLeave($tenantB, $employeeB, '2026-04-20', '2026-04-21', 'approved', 'Other Tenant April Match');

        return [$tenantA, $tenantB, $employeeA, $employeeB, $admin];
    }

    protected function makeTenant(string $slug): Tenant
    {
        return Tenant::create([
            'name' => ucwords(str_replace('-', ' ', $slug)),
            'slug' => $slug,
            'domain' => $slug.'.test',
            'status' => 'active',
        ]);
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

    protected function makeEmployee(Tenant $tenant, string $code, string $name, string $email, ?User $user = null): Employee
    {
        return Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user?->id,
            'employee_code' => $code,
            'name' => $name,
            'email' => $email,
            'status' => 'active',
        ]);
    }

    protected function makeLeave(Tenant $tenant, Employee $employee, string $startDate, string $endDate, string $status, string $reason): Leave
    {
        return Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => $reason,
            'status' => $status,
        ]);
    }
}