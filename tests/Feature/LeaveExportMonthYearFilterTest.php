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

class LeaveExportMonthYearFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_rows_follow_selected_month_and_year_filter(): void
    {
        Carbon::setTestNow('2026-04-17 08:00:00');
        Excel::fake();

        $tenant = Tenant::create([
            'name' => 'Leave Export Month Year Tenant',
            'slug' => 'leave-export-month-year-tenant',
            'domain' => 'leave-export-month-year-tenant.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Leave Export Month Year Admin',
            'email' => 'leave-export-month-year-admin@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'LVE-EXP-MY-1',
            'name' => 'Leave Export Month Year Employee',
            'email' => 'leave-export-month-year-employee@example.test',
            'status' => 'active',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => '2026-04-10',
            'end_date' => '2026-04-11',
            'reason' => 'April 2026 included',
            'status' => 'approved',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'sick',
            'start_date' => '2026-05-10',
            'end_date' => '2026-05-10',
            'reason' => 'May 2026 excluded',
            'status' => 'pending',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'permission',
            'start_date' => '2025-04-10',
            'end_date' => '2025-04-12',
            'reason' => 'April 2025 excluded',
            'status' => 'rejected',
        ]);

        $response = $this->actingAs($admin)->get(route('leaves.export', [
            'tenant_id' => $tenant->id,
            'month' => 4,
            'year' => 2026,
            'format' => 'xlsx',
        ]));

        $response->assertOk();

        Excel::assertDownloaded('leaves-export_20260417_tenant-leave-export-month-year-tenant_month-04_year-2026.xlsx', function (LeavesWorkbookExport $export) {
            $sheets = $export->sheets();
            $rows = $sheets[0]->collection()->toArray();

            $this->assertCount(1, $rows);
            $this->assertSame('April 2026 included', $rows[0]['reason']);
            $this->assertSame('2026-04-10', $rows[0]['start_date']);

            return true;
        });
    }
}