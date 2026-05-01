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

class LeaveEmployeeExportCardConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_export_filtered_summary_matches_detail_cards(): void
    {
        Carbon::setTestNow('2026-04-17 08:00:00');
        Excel::fake();

        $tenant = Tenant::create([
            'name' => 'Leave Employee Export Card Tenant',
            'slug' => 'leave-employee-export-card-tenant',
            'domain' => 'leave-employee-export-card-tenant.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Leave Employee Export Card Admin',
            'email' => 'leave-employee-export-card-admin@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'LVE-EMP-CRD-EXP-1',
            'name' => 'Leave Employee Export Card Employee',
            'email' => 'leave-employee-export-card-employee@example.test',
            'status' => 'active',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => '2026-04-08',
            'end_date' => '2026-04-09',
            'reason' => 'Card consistency approved',
            'status' => 'approved',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => '2026-04-12',
            'end_date' => '2026-04-12',
            'reason' => 'Card consistency rejected',
            'status' => 'rejected',
        ]);

        $detailResponse = $this->actingAs($admin)->get(route('employees.leaves.show', [
            'employee' => $employee,
            'month' => 4,
            'year' => 2026,
        ]));

        $detailResponse->assertOk();
        $cards = collect($detailResponse->viewData('employeeCardSummary'));

        $this->assertSame(1, $cards->firstWhere('status', 'approved')['requests']);
        $this->assertSame(2, $cards->firstWhere('status', 'approved')['days']);
        $this->assertSame(1, $cards->firstWhere('status', 'rejected')['requests']);
        $this->assertSame(1, $cards->firstWhere('status', 'rejected')['days']);

        $exportResponse = $this->actingAs($admin)->get(route('employees.leaves.export', [
            'employee' => $employee,
            'month' => 4,
            'year' => 2026,
            'format' => 'xlsx',
        ]));

        $exportResponse->assertOk();

        Excel::assertDownloaded('employee-leaves-export_20260417_employee-leave-employee-export-card-employee_tenant-leave-employee-export-card-tenant_month-04_year-2026.xlsx', function (LeavesWorkbookExport $export) {
            $filteredSummaryRows = $export->sheets()[3]->collection()->toArray();

            $this->assertContains(['April 2026', 'Approved', 1, 2], $filteredSummaryRows);
            $this->assertContains(['April 2026', 'Rejected', 1, 1], $filteredSummaryRows);
            $this->assertContains(['April 2026', 'Pending', 0, 0], $filteredSummaryRows);

            return true;
        });
    }
}