<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveDashboardRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_hr_sees_cross_tenant_dashboard_aggregates(): void
    {
        [$tenantA, $tenantB, $employeeA, $employeeB, $admin] = $this->seedRoleScopeData();

        $response = $this->actingAs($admin)->get(route('leaves.index'));

        $response->assertOk();

        $summary = $response->viewData('summary');

        $this->assertSame(['requests' => 1, 'days' => 2], $summary['pending']);
        $this->assertSame(['requests' => 1, 'days' => 3], $summary['approved']);
        $this->assertSame(['requests' => 1, 'days' => 4], $summary['rejected']);
    }

    public function test_manager_sees_tenant_scoped_dashboard_aggregates(): void
    {
        [$tenantA, $tenantB, $employeeA, $employeeB, $admin] = $this->seedRoleScopeData();
        $manager = $this->makeUser('manager', $tenantA, 'leave-dashboard-role-manager@example.test', 'Leave Dashboard Role Manager');

        $response = $this->actingAs($manager)->get(route('leaves.index'));

        $response->assertOk();

        $summary = $response->viewData('summary');

        $this->assertSame(['requests' => 1, 'days' => 2], $summary['pending']);
        $this->assertSame(['requests' => 1, 'days' => 3], $summary['approved']);
        $this->assertSame(['requests' => 0, 'days' => 0], $summary['rejected']);
    }

    public function test_employee_sees_only_own_dashboard_aggregates(): void
    {
        $tenant = $this->makeTenant('leave-dashboard-role-employee-tenant');
        $employeeUser = $this->makeUser('employee', $tenant, 'leave-dashboard-role-employee@example.test', 'Leave Dashboard Role Employee');
        $otherUser = $this->makeUser('employee', $tenant, 'leave-dashboard-role-other@example.test', 'Leave Dashboard Role Other');
        $employee = $this->makeEmployee($tenant, 'LVE-ROLE-E1', 'Leave Dashboard Role Employee A', 'leave-dashboard-role-employee-a@example.test', $employeeUser);
        $otherEmployee = $this->makeEmployee($tenant, 'LVE-ROLE-E2', 'Leave Dashboard Role Employee B', 'leave-dashboard-role-employee-b@example.test', $otherUser);

        $this->makeLeave($tenant, $employee, '2026-04-01', '2026-04-02', 'pending');
        $this->makeLeave($tenant, $employee, '2026-04-05', '2026-04-05', 'approved');
        $this->makeLeave($tenant, $otherEmployee, '2026-04-08', '2026-04-11', 'rejected');

        $response = $this->actingAs($employeeUser)->get(route('leaves.index'));

        $response->assertOk();

        $summary = $response->viewData('summary');

        $this->assertSame(['requests' => 1, 'days' => 2], $summary['pending']);
        $this->assertSame(['requests' => 1, 'days' => 1], $summary['approved']);
        $this->assertSame(['requests' => 0, 'days' => 0], $summary['rejected']);
    }

    protected function seedRoleScopeData(): array
    {
        $tenantA = $this->makeTenant('leave-dashboard-role-tenant-a');
        $tenantB = $this->makeTenant('leave-dashboard-role-tenant-b');
        $employeeA = $this->makeEmployee($tenantA, 'LVE-ROLE-A1', 'Leave Dashboard Role Employee A', 'leave-dashboard-role-a@example.test');
        $employeeB = $this->makeEmployee($tenantB, 'LVE-ROLE-B1', 'Leave Dashboard Role Employee B', 'leave-dashboard-role-b@example.test');
        $admin = $this->makeUser('admin_hr', $tenantA, 'leave-dashboard-role-admin@example.test', 'Leave Dashboard Role Admin');

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
            'reason' => 'Role '.$status,
            'status' => $status,
        ]);
    }
}