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

class LeaveEmployeeSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_detail_page_and_export_show_summary_for_selected_employee(): void
    {
        Carbon::setTestNow('2026-04-17 08:00:00');
        Excel::fake();

        $tenant = Tenant::create([
            'name' => 'Leave Employee Summary Tenant',
            'slug' => 'leave-employee-summary-tenant',
            'domain' => 'leave-employee-summary-tenant.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Leave Employee Summary Admin',
            'email' => 'leave-employee-summary-admin@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'LVE-EMP-SUM-1',
            'name' => 'Leave Employee Summary Employee',
            'email' => 'leave-employee-summary-employee@example.test',
            'status' => 'active',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => '2026-04-10',
            'end_date' => '2026-04-11',
            'reason' => 'Employee pending leave',
            'status' => 'pending',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'permission',
            'start_date' => '2026-04-12',
            'end_date' => '2026-04-14',
            'reason' => 'Employee approved leave',
            'status' => 'approved',
        ]);

        $detailResponse = $this->actingAs($admin)->get(route('employees.leaves.show', $employee));

        $detailResponse->assertOk();
        $detailResponse->assertSee('Pending: 1 requests / 2 days');
        $detailResponse->assertSee('Approved: 1 requests / 3 days');
        $detailResponse->assertSee('Rejected: 0 requests / 0 days');
        $detailResponse->assertSee('Employee pending leave');
        $detailResponse->assertSee('Employee approved leave');

        $exportResponse = $this->actingAs($admin)->get(route('employees.leaves.export', [
            'employee' => $employee,
            'format' => 'xlsx',
        ]));

        $exportResponse->assertOk();

        Excel::assertDownloaded('employee-leaves-export_20260417_employee-leave-employee-summary-employee_tenant-leave-employee-summary-tenant.xlsx', function (LeavesWorkbookExport $export) {
            $sheets = $export->sheets();

            $this->assertCount(4, $sheets);
            $this->assertSame(['Summary', 'Pending Requests', 1, 'Pending Days', 2, 'Approved Requests', 1, 'Approved Days', 3, 'Rejected Requests', 0, 'Rejected Days', 0], $sheets[0]->headings()[0]);
            $this->assertCount(2, $sheets[0]->collection()->toArray());
            $this->assertContains(['Apr 2026', 'Pending', 1, 2], $sheets[1]->collection()->toArray());
            $this->assertContains(['Current Scope', 'Approved', 1, 3], $sheets[3]->collection()->toArray());

            return true;
        });
    }
}