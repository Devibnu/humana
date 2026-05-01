<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveEmployeeDetailMonthYearFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_detail_summary_and_table_follow_selected_month_year(): void
    {
        $tenant = Tenant::create([
            'name' => 'Leave Employee Detail Filter Tenant',
            'slug' => 'leave-employee-detail-filter-tenant',
            'domain' => 'leave-employee-detail-filter-tenant.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Leave Employee Detail Filter Admin',
            'email' => 'leave-employee-detail-filter-admin@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'LVE-EMP-DTL-1',
            'name' => 'Leave Employee Detail Filter Employee',
            'email' => 'leave-employee-detail-filter-employee@example.test',
            'status' => 'active',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => '2026-04-10',
            'end_date' => '2026-04-11',
            'reason' => 'April pending included',
            'status' => 'pending',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'permission',
            'start_date' => '2026-04-20',
            'end_date' => '2026-04-22',
            'reason' => 'April approved included',
            'status' => 'approved',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'sick',
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-01',
            'reason' => 'May excluded',
            'status' => 'rejected',
        ]);

        $response = $this->actingAs($admin)->get(route('employees.leaves.show', [
            'employee' => $employee,
            'month' => 4,
            'year' => 2026,
        ]));

        $response->assertOk();
        $response->assertSee('Filtered scope: April 2026');
        $response->assertSee('Pending: 1 requests / 2 days');
        $response->assertSee('Approved: 1 requests / 3 days');
        $response->assertSee('Rejected: 0 requests / 0 days');
        $response->assertSee('April pending included');
        $response->assertSee('April approved included');
        $response->assertDontSee('May excluded');
    }
}