<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveEmployeeSparklineTooltipTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_detail_renders_tooltip_label_with_days_suffix(): void
    {
        $tenant = Tenant::create([
            'name' => 'Leave Sparkline Tooltip Tenant',
            'slug' => 'leave-sparkline-tooltip-tenant',
            'domain' => 'leave-sparkline-tooltip-tenant.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Leave Sparkline Tooltip Admin',
            'email' => 'leave-sparkline-tooltip-admin@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'LVE-SPK-T1',
            'name' => 'Leave Sparkline Tooltip Employee',
            'email' => 'leave-sparkline-tooltip-employee@example.test',
            'status' => 'active',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-03',
            'reason' => 'Tooltip leave',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($admin)->get(route('employees.leaves.show', [
            'employee' => $employee,
            'year' => 2026,
        ]));

        $response->assertOk();
        $response->assertSee("return context.parsed.y + ' days';", false);

        $sparkline = $response->viewData('employeeMonthlySparkline');

        $this->assertSame('days', $sparkline['tooltip_suffix']);
        $this->assertTrue($sparkline['has_data']);
    }
}