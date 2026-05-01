<?php

namespace Tests\Feature;

use App\Exports\LeavesCsvExport;
use App\Exports\LeavesWorkbookExport;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class LeaveExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $fakeFilePath = base_path('vendor/maatwebsite/excel/src/Fakes/fake_file');

        if (! file_exists($fakeFilePath)) {
            @mkdir(dirname($fakeFilePath), 0777, true);
            file_put_contents($fakeFilePath, 'fake');
        }
    }

    public function test_admin_hr_can_export_leaves_csv_with_active_filters(): void
    {
        Carbon::setTestNow('2026-04-17 08:00:00');
        Excel::fake();

        $tenantA = $this->makeTenant('leave-export-tenant-a');
        $tenantB = $this->makeTenant('leave-export-tenant-b');
        $admin = $this->makeUser('admin_hr', $tenantA, 'leave-export-admin@example.test', 'Leave Export Admin');
        $employeeA = $this->makeEmployee($tenantA, 'LVE-EXP-A1', 'Leave Export Employee A', 'leave-export-employee-a@example.test');
        $employeeB = $this->makeEmployee($tenantB, 'LVE-EXP-B1', 'Leave Export Employee B', 'leave-export-employee-b@example.test');

        $this->makeLeave($tenantA, $employeeA, 'annual', 'approved', 'A leave export row');
        $this->makeLeave($tenantA, $employeeA, 'sick', 'pending', 'Unmatched leave row');
        $this->makeLeave($tenantB, $employeeB, 'annual', 'approved', 'Other tenant leave row');

        $response = $this->actingAs($admin)->get(route('leaves.export', [
            'tenant_id' => $tenantA->id,
            'employee_id' => $employeeA->id,
            'status' => 'approved',
        ]));

        $response->assertOk();

        Excel::assertDownloaded('leaves-export_20260417_tenant-leave-export-tenant-a_employee-leave-export-employee-a_status-approved.csv', function (LeavesCsvExport $export) {
            $rows = $export->array();

            $this->assertSame(['Summary', 'Pending Requests', 1, 'Pending Days', 2, 'Approved Requests', 1, 'Approved Days', 2, 'Rejected Requests', 0, 'Rejected Days', 0], $rows[0]);
            $this->assertContains(['LVE-EXP-A1', 'Leave Export Employee A', 'Leave Export Tenant A', 'Cuti Tahunan', '2026-04-20', '2026-04-21', 2, 'approved', 'A leave export row'], $rows);
            $this->assertContains(['Monthly Summary'], $rows);
            $this->assertContains(['Apr 2026', 'Pending', 1, 2], $rows);
            $this->assertContains(['Annual Summary'], $rows);
            $this->assertContains(['2026', 'Approved', 1, 2], $rows);
            $this->assertContains(['Filtered Summary'], $rows);
            $this->assertContains(['Current Scope', 'Approved', 1, 2], $rows);

            return true;
        });
    }

    public function test_admin_hr_can_export_leaves_xlsx_with_active_filters(): void
    {
        Carbon::setTestNow('2026-04-17 08:00:00');
        Excel::fake();

        $tenantA = $this->makeTenant('leave-xlsx-tenant-a');
        $tenantB = $this->makeTenant('leave-xlsx-tenant-b');
        $admin = $this->makeUser('admin_hr', $tenantA, 'leave-xlsx-admin@example.test', 'Leave Xlsx Admin');
        $employeeA = $this->makeEmployee($tenantA, 'LVE-XLSX-A1', 'Leave Xlsx Employee A', 'leave-xlsx-employee-a@example.test');
        $employeeB = $this->makeEmployee($tenantB, 'LVE-XLSX-B1', 'Leave Xlsx Employee B', 'leave-xlsx-employee-b@example.test');

        $this->makeLeave($tenantA, $employeeA, 'permission', 'rejected', 'Rejected leave export row');
        $this->makeLeave($tenantA, $employeeA, 'annual', 'pending', 'Unmatched leave row');
        $this->makeLeave($tenantB, $employeeB, 'permission', 'rejected', 'Other tenant row');

        $response = $this->actingAs($admin)->get(route('leaves.export', [
            'tenant_id' => $tenantA->id,
            'employee_id' => $employeeA->id,
            'status' => 'rejected',
            'format' => 'xlsx',
        ]));

        $response->assertOk();

        Excel::assertDownloaded('leaves-export_20260417_tenant-leave-xlsx-tenant-a_employee-leave-xlsx-employee-a_status-rejected.xlsx', function (LeavesWorkbookExport $export) {
            $sheets = $export->sheets();

            $this->assertCount(4, $sheets);
            $this->assertSame('Leaves Leave Xlsx Tenant A Reje', $sheets[0]->title());
            $this->assertSame(['Summary', 'Pending Requests', 1, 'Pending Days', 2, 'Approved Requests', 0, 'Approved Days', 0, 'Rejected Requests', 1, 'Rejected Days', 2], $sheets[0]->headings()[0]);
            $this->assertSame('Monthly Summary', $sheets[1]->title());
            $this->assertSame(['month', 'status', 'total_requests', 'total_days'], $sheets[1]->headings());
            $this->assertContains(['Apr 2026', 'Rejected', 1, 2], $sheets[1]->collection()->toArray());
            $this->assertSame('Annual Summary', $sheets[2]->title());
            $this->assertSame(['year', 'status', 'total_requests', 'total_days'], $sheets[2]->headings());
            $this->assertContains(['2026', 'Rejected', 1, 2], $sheets[2]->collection()->toArray());
            $this->assertSame('Filtered Summary', $sheets[3]->title());
            $this->assertSame(['filter_scope', 'status', 'total_requests', 'total_days'], $sheets[3]->headings());
            $this->assertContains(['Current Scope', 'Rejected', 1, 2], $sheets[3]->collection()->toArray());

            return true;
        });
    }

    protected function makeTenant(string $slug): Tenant
    {
        return Tenant::create([
            'name' => ucwords(str_replace('-', ' ', $slug)),
            'slug' => $slug,
            'domain' => $slug.'.test',
            'status' => 'active',
        ]);
    }

    protected function makeUser(string $role, Tenant $tenant, string $email, string $name): User
    {
        return User::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'email' => $email,
            'password' => 'password',
            'role' => $role,
            'status' => 'active',
        ]);
    }

    protected function makeEmployee(Tenant $tenant, string $code, string $name, string $email): Employee
    {
        return Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => $code,
            'name' => $name,
            'email' => $email,
            'status' => 'active',
        ]);
    }

    protected function makeLeave(Tenant $tenant, Employee $employee, string $type, string $status, string $reason): Leave
    {
        return Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $this->resolveLeaveTypeId($tenant, $type),
            'start_date' => '2026-04-20',
            'end_date' => '2026-04-21',
            'reason' => $reason,
            'status' => $status,
        ]);
    }

    protected function resolveLeaveTypeId(Tenant $tenant, string $type): int
    {
        $definition = LeaveType::definitionFromInput($type);

        return (int) LeaveType::query()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => $definition['name']],
            ['is_paid' => $definition['is_paid']]
        )->id;
    }
}