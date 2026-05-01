<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveEmployeeSparklineSplitRangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_detail_splits_cross_month_leave_into_each_month(): void
    {
        $tenant = Tenant::create([
            'name' => 'Leave Sparkline Split Tenant',
            'slug' => 'leave-sparkline-split-tenant',
            'domain' => 'leave-sparkline-split-tenant.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Leave Sparkline Split Admin',
            'email' => 'leave-sparkline-split-admin@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'LVE-SPK-S1',
            'name' => 'Leave Sparkline Split Employee',
            'email' => 'leave-sparkline-split-employee@example.test',
            'status' => 'active',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => '2026-01-30',
            'end_date' => '2026-02-02',
            'reason' => 'Cross month leave',
            'status' => 'approved',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => '2025-12-31',
            'end_date' => '2026-01-02',
            'reason' => 'Cross year leave',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($admin)->get(route('employees.leaves.show', [
            'employee' => $employee,
            'year' => 2026,
        ]));

        $response->assertOk();

        $sparkline = $response->viewData('employeeMonthlySparkline');

        $this->assertSame('split_range', $sparkline['aggregation_mode']);
        $this->assertSame([4, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0], $sparkline['days']);
    }
}