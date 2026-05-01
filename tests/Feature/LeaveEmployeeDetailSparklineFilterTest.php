<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveEmployeeDetailSparklineFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_hr_sparkline_follows_active_year(): void
    {
        [$tenant, $employee, $admin] = $this->seedSparklineData();

        $response = $this->actingAs($admin)->get(route('employees.leaves.show', [
            'employee' => $employee,
            'year' => 2026,
        ]));

        $response->assertOk();

        $sparkline = $response->viewData('employeeMonthlySparkline');

        $this->assertSame(2026, $sparkline['year']);
        $this->assertSame([0, 2, 0, 3, 0, 0, 0, 0, 0, 0, 0, 0], $sparkline['days']);
    }

    public function test_manager_sparkline_follows_active_year_for_same_tenant_employee(): void
    {
        [$tenant, $employee, $admin] = $this->seedSparklineData();
        $manager = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Leave Sparkline Manager',
            'email' => 'leave-sparkline-manager@example.test',
            'password' => 'password',
            'role' => 'manager',
            'status' => 'active',
        ]);

        $response = $this->actingAs($manager)->get(route('employees.leaves.show', [
            'employee' => $employee,
            'year' => 2025,
        ]));

        $response->assertOk();

        $sparkline = $response->viewData('employeeMonthlySparkline');

        $this->assertSame(2025, $sparkline['year']);
        $this->assertSame([0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0], $sparkline['days']);
    }

    public function test_employee_sparkline_only_uses_own_data_for_active_year(): void
    {
        $tenant = Tenant::create([
            'name' => 'Leave Sparkline Employee Tenant',
            'slug' => 'leave-sparkline-employee-tenant',
            'domain' => 'leave-sparkline-employee-tenant.test',
            'status' => 'active',
        ]);

        $employeeUser = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Leave Sparkline Employee User',
            'email' => 'leave-sparkline-employee-user@example.test',
            'password' => 'password',
            'role' => 'employee',
            'status' => 'active',
        ]);

        $otherUser = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Leave Sparkline Other User',
            'email' => 'leave-sparkline-other-user@example.test',
            'password' => 'password',
            'role' => 'employee',
            'status' => 'active',
        ]);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $employeeUser->id,
            'employee_code' => 'LVE-SPK-E1',
            'name' => 'Leave Sparkline Employee A',
            'email' => 'leave-sparkline-employee-a@example.test',
            'status' => 'active',
        ]);

        $otherEmployee = Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $otherUser->id,
            'employee_code' => 'LVE-SPK-E2',
            'name' => 'Leave Sparkline Employee B',
            'email' => 'leave-sparkline-employee-b@example.test',
            'status' => 'active',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-02',
            'reason' => 'Own sparkline leave',
            'status' => 'approved',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $otherEmployee->id,
            'leave_type' => 'annual',
            'start_date' => '2026-04-05',
            'end_date' => '2026-04-08',
            'reason' => 'Other employee leave',
            'status' => 'rejected',
        ]);

        $response = $this->actingAs($employeeUser)->get(route('employees.leaves.show', [
            'employee' => $employee,
            'year' => 2026,
        ]));

        $response->assertOk();

        $sparkline = $response->viewData('employeeMonthlySparkline');

        $this->assertSame(2026, $sparkline['year']);
        $this->assertSame([0, 0, 0, 2, 0, 0, 0, 0, 0, 0, 0, 0], $sparkline['days']);
    }

    protected function seedSparklineData(): array
    {
        $tenant = Tenant::create([
            'name' => 'Leave Sparkline Filter Tenant',
            'slug' => 'leave-sparkline-filter-tenant',
            'domain' => 'leave-sparkline-filter-tenant.test',
            'status' => 'active',
        ]);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'LVE-SPK-F1',
            'name' => 'Leave Sparkline Filter Employee',
            'email' => 'leave-sparkline-filter-employee@example.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Leave Sparkline Filter Admin',
            'email' => 'leave-sparkline-filter-admin@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => '2026-02-02',
            'end_date' => '2026-02-03',
            'reason' => '2026 February leave',
            'status' => 'pending',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => '2026-04-10',
            'end_date' => '2026-04-12',
            'reason' => '2026 April leave',
            'status' => 'approved',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => '2025-04-10',
            'end_date' => '2025-04-10',
            'reason' => '2025 April leave',
            'status' => 'approved',
        ]);

        return [$tenant, $employee, $admin];
    }
}