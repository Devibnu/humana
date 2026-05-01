<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkLocation;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceAnalyticsTenantFilterTest extends TestCase
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

    public function test_dropdown_tenant_tampil_untuk_admin_multi_tenant(): void
    {
        [$admin, $tenantA, $tenantB] = $this->makeContext();

        $response = $this->actingAs($admin)->get(route('attendances.analytics'));

        $response->assertOk();
        $response->assertSee('data-testid="attendance-analytics-tenant-filter"', false);
        $response->assertSee('Pilih tenant untuk melihat analitik absensi');
        $response->assertSee('Tenant: '.$tenantA->name);
        $response->assertSee($tenantA->name);
        $response->assertSee($tenantB->name);
        $this->assertTrue($response->viewData('canSwitchTenant'));
        $this->assertSame($tenantA->id, $response->viewData('tenant')->id);
    }

    public function test_data_analytics_berubah_sesuai_tenant_yang_dipilih(): void
    {
        [$admin, $tenantA, $tenantB] = $this->makeContext();

        $response = $this->actingAs($admin)->get(route('attendances.analytics', [
            'tenant_id' => $tenantB->id,
            'year' => 2026,
            'month' => 4,
        ]));

        $response->assertOk();
        $response->assertSee('Tenant: '.$tenantB->name);

        $monthSummary = $response->viewData('monthSummary');
        $yearSummary = $response->viewData('yearSummary');

        $this->assertSame($tenantB->id, $response->viewData('tenant')->id);
        $this->assertSame(2, $monthSummary['total_attendances']);
        $this->assertSame(1, $monthSummary['status_counts']['present']);
        $this->assertSame(0, $monthSummary['status_counts']['leave']);
        $this->assertSame(1, $monthSummary['status_counts']['sick']);
        $this->assertSame(0, $monthSummary['status_counts']['absent']);
        $this->assertSame('9 jam 00 menit', $monthSummary['total_work_hours_label']);
        $this->assertSame(3, $yearSummary['total_attendances']);
        $this->assertSame([0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 1], collect($response->viewData('monthlyTrendChart')['datasets'])->keyBy('label')['Hadir']['data']);
    }

    public function test_manager_tetap_locked_ke_tenant_sendiri_dan_tidak_bisa_switch(): void
    {
        [, $tenantA, $tenantB, $manager] = $this->makeContext();

        $response = $this->actingAs($manager)->get(route('attendances.analytics', [
            'tenant_id' => $tenantB->id,
            'year' => 2026,
            'month' => 4,
        ]));

        $response->assertOk();
        $response->assertSee('Tenant: '.$tenantA->name);
        $response->assertDontSee('data-testid="attendance-analytics-tenant-filter"', false);

        $monthSummary = $response->viewData('monthSummary');

        $this->assertSame($tenantA->id, $response->viewData('tenant')->id);
        $this->assertFalse($response->viewData('canSwitchTenant'));
        $this->assertSame(3, $monthSummary['total_attendances']);
        $this->assertSame(1, $monthSummary['status_counts']['present']);
        $this->assertSame(1, $monthSummary['status_counts']['leave']);
        $this->assertSame(0, $monthSummary['status_counts']['sick']);
        $this->assertSame(1, $monthSummary['status_counts']['absent']);
    }

    public function test_employee_tetap_tidak_bisa_switch_scope_analytics(): void
    {
        [, , , , $employeeUser] = $this->makeContext();

        $response = $this->actingAs($employeeUser)->get(route('attendances.analytics', [
            'tenant_id' => 999999,
        ]));

        $response->assertForbidden();
    }

    protected function makeContext(): array
    {
        $tenantA = Tenant::create([
            'name' => 'Tenant Analitik Alpha',
            'slug' => 'tenant-analitik-alpha',
            'domain' => 'tenant-analitik-alpha.test',
            'status' => 'active',
        ]);

        $tenantB = Tenant::create([
            'name' => 'Tenant Analitik Beta',
            'slug' => 'tenant-analitik-beta',
            'domain' => 'tenant-analitik-beta.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenantA->id,
            'name' => 'Admin Analitik Tenant',
            'email' => 'admin-analitik-tenant@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $manager = User::create([
            'tenant_id' => $tenantA->id,
            'name' => 'Manager Analitik Tenant',
            'email' => 'manager-analitik-tenant@example.test',
            'password' => 'password',
            'role' => 'manager',
            'status' => 'active',
        ]);

        $employeeUser = User::create([
            'tenant_id' => $tenantA->id,
            'name' => 'Employee Analitik Tenant',
            'email' => 'employee-analitik-tenant@example.test',
            'password' => 'password',
            'role' => 'employee',
            'status' => 'active',
        ]);

        $workLocationA = WorkLocation::create([
            'tenant_id' => $tenantA->id,
            'name' => 'Kantor Alpha',
            'address' => 'Jakarta',
            'latitude' => -6.2000000,
            'longitude' => 106.8166667,
            'radius' => 250,
        ]);

        $workLocationB = WorkLocation::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Kantor Beta',
            'address' => 'Bandung',
            'latitude' => -6.9147440,
            'longitude' => 107.6098100,
            'radius' => 250,
        ]);

        $employeeA1 = Employee::create([
            'tenant_id' => $tenantA->id,
            'work_location_id' => $workLocationA->id,
            'employee_code' => 'AAT-001',
            'name' => 'Pegawai Alpha 1',
            'email' => 'pegawai-alpha-1@example.test',
            'status' => 'active',
            'user_id' => $employeeUser->id,
        ]);

        $employeeA2 = Employee::create([
            'tenant_id' => $tenantA->id,
            'work_location_id' => $workLocationA->id,
            'employee_code' => 'AAT-002',
            'name' => 'Pegawai Alpha 2',
            'email' => 'pegawai-alpha-2@example.test',
            'status' => 'active',
        ]);

        $employeeA3 = Employee::create([
            'tenant_id' => $tenantA->id,
            'work_location_id' => $workLocationA->id,
            'employee_code' => 'AAT-003',
            'name' => 'Pegawai Alpha 3',
            'email' => 'pegawai-alpha-3@example.test',
            'status' => 'active',
        ]);

        $employeeB1 = Employee::create([
            'tenant_id' => $tenantB->id,
            'work_location_id' => $workLocationB->id,
            'employee_code' => 'ABT-001',
            'name' => 'Pegawai Beta 1',
            'email' => 'pegawai-beta-1@example.test',
            'status' => 'active',
        ]);

        $employeeB2 = Employee::create([
            'tenant_id' => $tenantB->id,
            'work_location_id' => $workLocationB->id,
            'employee_code' => 'ABT-002',
            'name' => 'Pegawai Beta 2',
            'email' => 'pegawai-beta-2@example.test',
            'status' => 'active',
        ]);

        Attendance::create([
            'tenant_id' => $tenantA->id,
            'employee_id' => $employeeA1->id,
            'date' => '2026-04-02',
            'check_in' => '08:00',
            'check_out' => '17:00',
            'status' => 'present',
        ]);

        Attendance::create([
            'tenant_id' => $tenantA->id,
            'employee_id' => $employeeA2->id,
            'date' => '2026-04-03',
            'status' => 'leave',
        ]);

        Attendance::create([
            'tenant_id' => $tenantA->id,
            'employee_id' => $employeeA3->id,
            'date' => '2026-04-04',
            'status' => 'absent',
        ]);

        Attendance::create([
            'tenant_id' => $tenantB->id,
            'employee_id' => $employeeB1->id,
            'date' => '2026-02-14',
            'check_in' => '08:00',
            'check_out' => '17:00',
            'status' => 'present',
        ]);

        Attendance::create([
            'tenant_id' => $tenantB->id,
            'employee_id' => $employeeB1->id,
            'date' => '2026-04-11',
            'check_in' => '08:00',
            'check_out' => '17:00',
            'status' => 'present',
        ]);

        Attendance::create([
            'tenant_id' => $tenantB->id,
            'employee_id' => $employeeB2->id,
            'date' => '2026-04-12',
            'status' => 'sick',
        ]);

        return [$admin, $tenantA, $tenantB, $manager, $employeeUser];
    }
}