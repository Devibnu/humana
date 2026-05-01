<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveEmployeeDetailSparklineTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_detail_sends_monthly_sparkline_data_to_view(): void
    {
        $tenant = Tenant::create([
            'name' => 'Leave Employee Sparkline Tenant',
            'slug' => 'leave-employee-sparkline-tenant',
            'domain' => 'leave-employee-sparkline-tenant.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Leave Employee Sparkline Admin',
            'email' => 'leave-employee-sparkline-admin@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'LVE-SPK-1',
            'name' => 'Leave Employee Sparkline Employee',
            'email' => 'leave-employee-sparkline-employee@example.test',
            'status' => 'active',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => '2026-01-10',
            'end_date' => '2026-01-12',
            'reason' => 'January leave',
            'status' => 'approved',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'permission',
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-02',
            'reason' => 'April leave',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->get(route('employees.leaves.show', [
            'employee' => $employee,
            'year' => 2026,
        ]));

        $response->assertOk();
        $response->assertSee('Monthly Leave Pattern');

        $sparkline = $response->viewData('employeeMonthlySparkline');

        $this->assertSame(2026, $sparkline['year']);
        $this->assertSame(['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'], $sparkline['labels']);
        $this->assertSame([3, 0, 0, 2, 0, 0, 0, 0, 0, 0, 0, 0], $sparkline['days']);
    }
}