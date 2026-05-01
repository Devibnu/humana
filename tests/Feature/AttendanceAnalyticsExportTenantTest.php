<?php

namespace Tests\Feature;

use App\Exports\AttendanceAnalyticsExport;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkLocation;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class AttendanceAnalyticsExportTenantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-04-21 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_nama_file_export_xlsx_menyertakan_tenant_label_untuk_manager(): void
    {
        [$manager] = $this->makeContext();

        Excel::fake();

        $response = $this->actingAs($manager)->get(route('attendances.analytics.export.xlsx', [
            'year' => 2026,
            'month' => 4,
        ]));

        $response->assertOk();

        Excel::assertDownloaded('attendance_analytics_attendance-analytics-export-tenant-label_2026_20260421.xlsx', function (AttendanceAnalyticsExport $export) {
            return count($export->sheets()) === 2;
        });
    }

    public function test_nama_file_export_pdf_menyertakan_label_semua_tenant_untuk_admin(): void
    {
        [, $admin] = $this->makeContext();

        $response = $this->actingAs($admin)->get(route('attendances.analytics.export.pdf', [
            'year' => 2026,
            'month' => 4,
        ]));

        $response->assertOk();
        $this->assertStringContainsString('attendance_analytics_attendance-analytics-export-tenant-label_2026_20260421.pdf', (string) $response->headers->get('content-disposition'));
    }

    protected function makeContext(): array
    {
        $tenant = Tenant::create([
            'name' => 'Attendance Analytics Export Tenant Label',
            'slug' => 'attendance-analytics-export-tenant-label',
            'domain' => 'attendance-analytics-export-tenant-label.test',
            'status' => 'active',
        ]);

        $manager = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Attendance Analytics Export Tenant Manager',
            'email' => 'attendance-analytics-export-tenant-manager@example.test',
            'password' => 'password',
            'role' => 'manager',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Attendance Analytics Export Tenant Admin',
            'email' => 'attendance-analytics-export-tenant-admin@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $workLocation = WorkLocation::create([
            'tenant_id' => $tenant->id,
            'name' => 'Attendance Analytics Export Tenant Office',
            'address' => 'Jakarta',
            'latitude' => -6.2000000,
            'longitude' => 106.8166667,
            'radius' => 250,
        ]);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'work_location_id' => $workLocation->id,
            'employee_code' => 'ATL-001',
            'name' => 'Attendance Analytics Export Tenant Employee',
            'email' => 'attendance-analytics-export-tenant-employee@example.test',
            'status' => 'active',
        ]);

        Attendance::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'date' => '2026-04-01',
            'check_in' => '08:00',
            'check_out' => '17:00',
            'status' => 'present',
        ]);

        return [$manager, $admin];
    }
}