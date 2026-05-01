<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendancesDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_attendances_dashboard_accessible_by_admin_and_summary_cards_correct(): void
    {
        [$admin, $tenant, $employeeA, $employeeB, $employeeC] = $this->makeDashboardContext();

        Attendance::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employeeA->id,
            'date' => now()->toDateString(),
            'check_in' => '08:00',
            'check_out' => '17:00',
            'status' => 'present',
        ]);

        Attendance::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employeeB->id,
            'date' => now()->toDateString(),
            'status' => 'leave',
        ]);

        Attendance::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employeeC->id,
            'date' => now()->toDateString(),
            'status' => 'sick',
        ]);

        Attendance::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employeeA->id,
            'date' => now()->subDay()->toDateString(),
            'status' => 'absent',
        ]);

        Attendance::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employeeB->id,
            'date' => now()->subDays(2)->toDateString(),
            'status' => 'present',
        ]);

        $response = $this->actingAs($admin)->get(route('attendances.dashboard'));

        $response->assertOk();
        $response->assertViewIs('attendances.dashboard');
        $response->assertSee('Dashboard Kehadiran');
        $response->assertSee('Ringkasan Kehadiran Operasional');
        $response->assertSee('Total Kehadiran Hari Ini');
        $response->assertSee('Hadir');
        $response->assertSee('Izin');
        $response->assertSee('Sakit');
        $response->assertSee('Alpha');

        $this->assertSame(3, $response->viewData('totalKehadiranHariIni'));
        $this->assertSame(1, $response->viewData('totalHadir'));
        $this->assertSame(1, $response->viewData('totalIzin'));
        $this->assertSame(1, $response->viewData('totalSakit'));
        $this->assertSame(0, $response->viewData('totalAlpha'));
        $this->assertSame(2, $response->viewData('totalTidakHadir'));
        $this->assertSame(3, $response->viewData('totalKaryawanScope'));

        $response->assertSee('data-testid="attendance-dashboard-card-total"', false);
        $response->assertSee('data-testid="attendance-dashboard-card-present"', false);
        $response->assertSee('data-testid="attendance-dashboard-card-leave"', false);
        $response->assertSee('data-testid="attendance-dashboard-card-sick"', false);
        $response->assertSee('data-testid="attendance-dashboard-card-absent"', false);
        $response->assertSee('data-testid="attendance-dashboard-status-chart-container"', false);
        $response->assertSee('data-testid="attendance-dashboard-trend-chart-container"', false);
        $response->assertSee('Hadir: 1');
        $response->assertSee('Izin: 1');
        $response->assertSee('Sakit: 1');
        $response->assertSee('Alpha: 0');
    }

    public function test_chart_data_tersedia_di_response(): void
    {
        [$admin, $tenant, $employeeA, $employeeB] = $this->makeDashboardContext(includeThirdEmployee: false);

        Attendance::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employeeA->id,
            'date' => now()->subDays(6)->toDateString(),
            'status' => 'present',
        ]);

        Attendance::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employeeA->id,
            'date' => now()->subDays(3)->toDateString(),
            'status' => 'late',
        ]);

        Attendance::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employeeB->id,
            'date' => now()->toDateString(),
            'status' => 'absent',
        ]);

        $response = $this->actingAs($admin)->get(route('attendances.dashboard'));

        $response->assertOk();

        $statusDistributionChart = $response->viewData('statusDistributionChart');
        $attendanceTrendChart = $response->viewData('attendanceTrendChart');

        $this->assertSame(['Hadir', 'Izin', 'Sakit', 'Alpha'], $statusDistributionChart['labels']);
        $this->assertSame([0, 0, 0, 1], $statusDistributionChart['counts']);
        $this->assertSame(7, count($attendanceTrendChart['labels']));
        $this->assertSame(7, count($attendanceTrendChart['counts']));
        $this->assertSame(1, $attendanceTrendChart['counts'][0]);
        $this->assertSame(1, $attendanceTrendChart['counts'][3]);
        $this->assertSame(1, $attendanceTrendChart['counts'][6]);
    }

    public function test_employee_diarahkan_ke_halaman_personal(): void
    {
        [, $tenant, $employeeA] = $this->makeDashboardContext();

        $employeeUser = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Employee Dashboard User',
            'email' => 'employee-dashboard-user@example.test',
            'password' => 'password',
            'role' => 'employee',
            'status' => 'active',
        ]);

        $employeeA->update([
            'user_id' => $employeeUser->id,
        ]);

        $response = $this->actingAs($employeeUser)->get(route('attendances.dashboard'));

        $response->assertRedirect(route('attendances.index'));
    }

    protected function makeDashboardContext(bool $includeThirdEmployee = true): array
    {
        $tenant = Tenant::create([
            'name' => 'Attendance Dashboard Tenant',
            'slug' => 'attendance-dashboard-tenant',
            'domain' => 'attendance-dashboard-tenant.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Attendance Dashboard Admin',
            'email' => 'attendance-dashboard-admin@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $workLocation = WorkLocation::create([
            'tenant_id' => $tenant->id,
            'name' => 'Attendance Dashboard Office',
            'address' => 'Jakarta',
            'latitude' => -6.2000000,
            'longitude' => 106.8166667,
            'radius' => 250,
        ]);

        $employeeA = Employee::create([
            'tenant_id' => $tenant->id,
            'work_location_id' => $workLocation->id,
            'employee_code' => 'ATD-001',
            'name' => 'Attendance Dashboard Employee A',
            'email' => 'attendance-dashboard-employee-a@example.test',
            'status' => 'active',
        ]);

        $employeeB = Employee::create([
            'tenant_id' => $tenant->id,
            'work_location_id' => $workLocation->id,
            'employee_code' => 'ATD-002',
            'name' => 'Attendance Dashboard Employee B',
            'email' => 'attendance-dashboard-employee-b@example.test',
            'status' => 'active',
        ]);

        $employeeC = null;

        if ($includeThirdEmployee) {
            $employeeC = Employee::create([
                'tenant_id' => $tenant->id,
                'work_location_id' => $workLocation->id,
                'employee_code' => 'ATD-003',
                'name' => 'Attendance Dashboard Employee C',
                'email' => 'attendance-dashboard-employee-c@example.test',
                'status' => 'active',
            ]);
        }

        return [$admin, $tenant, $employeeA, $employeeB, $employeeC];
    }
}