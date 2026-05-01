<?php

namespace Tests\Feature;

use App\Exports\LeavesCsvExport;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class LeaveExportLeaveTypeTest extends TestCase
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

    public function test_export_includes_leave_type_name_instead_of_string(): void
    {
        Excel::fake();

        $tenant = $this->makeTenant('leave-type-export-tenant');
        $admin = $this->makeUser('admin_hr', $tenant, 'leave-type-export-admin@example.test', 'Leave Type Export Admin');
        $employee = $this->makeEmployee($tenant, 'LTE-001', 'Leave Type Employee', 'leave-type-employee@example.test');
        $leaveType = LeaveType::create([
            'tenant_id' => $tenant->id,
            'name' => 'Cuti Tahunan',
            'is_paid' => true,
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-04-29',
            'end_date' => '2026-04-30',
            'reason' => 'Export leave type check',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($admin)->get(route('leaves.export', [
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'status' => 'approved',
        ]));

        $response->assertOk();

        Excel::assertDownloaded('leaves-export_'.now()->format('Ymd').'_tenant-leave-type-export-tenant_employee-leave-type-employee_status-approved.csv', function (LeavesCsvExport $export) {
            $rows = $export->array();

            $flattened = collect($rows)->flatten()->map(fn ($value) => (string) $value);

            $this->assertTrue($flattened->contains('Cuti Tahunan'));
            $this->assertFalse($flattened->contains('annual'));

            return true;
        });
    }

    public function test_report_view_shows_leave_type_name(): void
    {
        $tenant = $this->makeTenant('leave-type-report-tenant');
        $admin = $this->makeUser('admin_hr', $tenant, 'leave-type-report-admin@example.test', 'Leave Type Report Admin');
        $employee = $this->makeEmployee($tenant, 'LTR-001', 'Leave Type Report Employee', 'leave-type-report-employee@example.test');
        $leaveType = LeaveType::create([
            'tenant_id' => $tenant->id,
            'name' => 'Cuti Sakit',
            'is_paid' => true,
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-02',
            'reason' => 'Report leave type check',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->get(route('leaves.index', [
            'tenant_id' => $tenant->id,
        ]));

        $response->assertOk();
        $response->assertSee('Cuti Sakit');
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
}
