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

class LeaveEmployeeExportMonthYearConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_export_matches_detail_month_year_scope(): void
    {
        Carbon::setTestNow('2026-04-17 08:00:00');
        Excel::fake();

        $tenant = Tenant::create([
            'name' => 'Leave Employee Export Consistency Tenant',
            'slug' => 'leave-employee-export-consistency-tenant',
            'domain' => 'leave-employee-export-consistency-tenant.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Leave Employee Export Consistency Admin',
            'email' => 'leave-employee-export-consistency-admin@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'LVE-EMP-CNST-1',
            'name' => 'Leave Employee Export Consistency Employee',
            'email' => 'leave-employee-export-consistency-employee@example.test',
            'status' => 'active',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => '2026-04-05',
            'end_date' => '2026-04-06',
            'reason' => 'Consistency included',
            'status' => 'approved',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => '2026-06-05',
            'end_date' => '2026-06-05',
            'reason' => 'Consistency excluded',
            'status' => 'pending',
        ]);

        $detailResponse = $this->actingAs($admin)->get(route('employees.leaves.show', [
            'employee' => $employee,
            'month' => 4,
            'year' => 2026,
        ]));

        $detailResponse->assertOk();
        $detailResponse->assertSee('Consistency included');
        $detailResponse->assertDontSee('Consistency excluded');

        $exportResponse = $this->actingAs($admin)->get(route('employees.leaves.export', [
            'employee' => $employee,
            'month' => 4,
            'year' => 2026,
            'format' => 'xlsx',
        ]));

        $exportResponse->assertOk();

        Excel::assertDownloaded('employee-leaves-export_20260417_employee-leave-employee-export-consistency-employee_tenant-leave-employee-export-consistency-tenant_month-04_year-2026.xlsx', function (LeavesWorkbookExport $export) {
            $sheets = $export->sheets();
            $rows = $sheets[0]->collection()->toArray();

            $this->assertCount(1, $rows);
            $this->assertSame('Consistency included', $rows[0]['reason']);
            $this->assertContains(['April 2026', 'Approved', 1, 2], $sheets[3]->collection()->toArray());

            return true;
        });
    }
}