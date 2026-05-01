<?php

namespace Tests\Feature;

use App\Exports\LeavesWorkbookExport;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class LeaveEmployeeExportMonthYearFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_export_rows_follow_selected_month_and_year_filter(): void
    {
        Carbon::setTestNow('2026-04-17 08:00:00');
        Excel::fake();

        $tenant = Tenant::create([
            'name' => 'Leave Employee Export Month Year Tenant',
            'slug' => 'leave-employee-export-month-year-tenant',
            'domain' => 'leave-employee-export-month-year-tenant.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Leave Employee Export Month Year Admin',
            'email' => 'leave-employee-export-month-year-admin@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'LVE-EMP-EXP-MY-1',
            'name' => 'Leave Employee Export Month Year Employee',
            'email' => 'leave-employee-export-month-year-employee@example.test',
            'status' => 'active',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-03',
            'reason' => 'Employee April included',
            'status' => 'approved',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-02',
            'reason' => 'Employee June excluded',
            'status' => 'pending',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => '2025-04-01',
            'end_date' => '2025-04-01',
            'reason' => 'Employee April previous year excluded',
            'status' => 'rejected',
        ]);

        $response = $this->actingAs($admin)->get(route('employees.leaves.export', [
            'employee' => $employee,
            'month' => 4,
            'year' => 2026,
            'format' => 'xlsx',
        ]));

        $response->assertOk();

        Excel::assertDownloaded('employee-leaves-export_20260417_employee-leave-employee-export-month-year-employee_tenant-leave-employee-export-month-year-tenant_month-04_year-2026.xlsx', function (LeavesWorkbookExport $export) {
            $sheets = $export->sheets();
            $rows = $sheets[0]->collection()->toArray();

            $this->assertCount(1, $rows);
            $this->assertSame('Employee April included', $rows[0]['reason']);
            $this->assertSame('2026-04-01', $rows[0]['start_date']);

            return true;
        });
    }
}