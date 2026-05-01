<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class LeaveDashboardFilterRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_hr_month_year_filter_includes_all_tenants(): void
    {
        [$tenantA, $tenantB, $employeeA, $employeeB, $admin] = $this->seedRoleFilterData();

        $response = $this->actingAs($admin)->get(route('leaves.index', ['month' => 4, 'year' => 2026]));

        $response->assertOk();

        $filteredSummary = collect($response->viewData('filteredSummary'));

        $this->assertFilterRow($filteredSummary, 'April 2026', 'pending', 1, 2);
        $this->assertFilterRow($filteredSummary, 'April 2026', 'approved', 1, 3);
        $this->assertFilterRow($filteredSummary, 'April 2026', 'rejected', 1, 4);
    }

    public function test_manager_month_year_filter_stays_tenant_scoped(): void
    {
        [$tenantA, $tenantB, $employeeA, $employeeB, $admin] = $this->seedRoleFilterData();
        $manager = $this->makeUser('manager', $tenantA, 'leave-dashboard-filter-role-manager@example.test', 'Leave Dashboard Filter Role Manager');

        $response = $this->actingAs($manager)->get(route('leaves.index', ['month' => 4, 'year' => 2026]));

        $response->assertOk();

        $filteredSummary = collect($response->viewData('filteredSummary'));

        $this->assertFilterRow($filteredSummary, 'April 2026', 'pending', 1, 2);
        $this->assertFilterRow($filteredSummary, 'April 2026', 'approved', 1, 3);
        $this->assertFilterRow($filteredSummary, 'April 2026', 'rejected', 0, 0);
    }

    public function test_employee_month_year_filter_only_sees_own_summary(): void
    {
        $tenant = $this->makeTenant('leave-dashboard-filter-role-employee-tenant');
        $employeeUser = $this->makeUser('employee', $tenant, 'leave-dashboard-filter-role-employee@example.test', 'Leave Dashboard Filter Role Employee');
        $otherUser = $this->makeUser('employee', $tenant, 'leave-dashboard-filter-role-other@example.test', 'Leave Dashboard Filter Role Other');
        $employee = $this->makeEmployee($tenant, 'LVE-RFL-E1', 'Leave Dashboard Filter Role Employee A', 'leave-dashboard-filter-role-employee-a@example.test', $employeeUser);
        $otherEmployee = $this->makeEmployee($tenant, 'LVE-RFL-E2', 'Leave Dashboard Filter Role Employee B', 'leave-dashboard-filter-role-employee-b@example.test', $otherUser);

        $this->makeLeave($tenant, $employee, '2026-04-01', '2026-04-02', 'pending');
        $this->makeLeave($tenant, $employee, '2026-04-03', '2026-04-03', 'approved');
        $this->makeLeave($tenant, $otherEmployee, '2026-04-04', '2026-04-07', 'rejected');

        $response = $this->actingAs($employeeUser)->get(route('leaves.index', ['month' => 4, 'year' => 2026, 'employee_id' => $otherEmployee->id]));

        $response->assertOk();

        $filteredSummary = collect($response->viewData('filteredSummary'));

        $this->assertFilterRow($filteredSummary, 'April 2026', 'pending', 1, 2);
        $this->assertFilterRow($filteredSummary, 'April 2026', 'approved', 1, 1);
        $this->assertFilterRow($filteredSummary, 'April 2026', 'rejected', 0, 0);
    }

    protected function assertFilterRow(Collection $rows, string $scope, string $status, int $requests, int $days): void
    {
        $row = $rows->first(fn (array $row) => $row['filter_scope'] === $scope && $row['status'] === $status);

        $this->assertNotNull($row);
        $this->assertSame($requests, $row['requests']);
        $this->assertSame($days, $row['days']);
    }

    protected function seedRoleFilterData(): array
    {
        $tenantA = $this->makeTenant('leave-dashboard-filter-role-tenant-a');
        $tenantB = $this->makeTenant('leave-dashboard-filter-role-tenant-b');
        $employeeA = $this->makeEmployee($tenantA, 'LVE-RFL-A1', 'Leave Dashboard Filter Role Employee A', 'leave-dashboard-filter-role-a@example.test');
        $employeeB = $this->makeEmployee($tenantB, 'LVE-RFL-B1', 'Leave Dashboard Filter Role Employee B', 'leave-dashboard-filter-role-b@example.test');
        $admin = $this->makeUser('admin_hr', $tenantA, 'leave-dashboard-filter-role-admin@example.test', 'Leave Dashboard Filter Role Admin');

        $this->makeLeave($tenantA, $employeeA, '2026-04-01', '2026-04-02', 'pending');
        $this->makeLeave($tenantA, $employeeA, '2026-04-03', '2026-04-05', 'approved');
        $this->makeLeave($tenantB, $employeeB, '2026-04-06', '2026-04-09', 'rejected');

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

    protected function makeLeave(Tenant $tenant, Employee $employee, string $startDate, string $endDate, string $status): Leave
    {
        return Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => 'Role filter '.$status,
            'status' => $status,
        ]);
    }
}