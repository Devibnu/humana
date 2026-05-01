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

class AttendancesAnalyticsTest extends TestCase
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

    public function test_route_attendances_analytics_accessible_by_admin(): void
    {
        [$admin] = $this->makeAnalyticsContextWithAttendances();

        $response = $this->actingAs($admin)->get(route('attendances.analytics'));

        $response->assertOk();
        $response->assertViewIs('attendances.analytics');
        $response->assertSeeText('Dashboard Analitik Absensi Bulanan & Tahunan');
        $response->assertSee('Total Kehadiran Bulan Ini');
        $response->assertSee('Total Kehadiran Tahun Ini');
        $response->assertSee('Distribusi Status');
        $response->assertSee('Tren Absensi per Status 12 Bulan Terakhir');
        $response->assertSee('Distribusi Status 5 Tahun Terakhir');
        $response->assertSee('data-testid="attendance-analytics-tenant-scope-badge"', false);
        $response->assertSee('data-testid="attendance-analytics-card-month-total"', false);
        $response->assertSee('data-testid="attendance-analytics-card-year-total"', false);
        $response->assertSee('data-testid="attendance-analytics-monthly-trend-chart-container"', false);
        $response->assertSee('data-testid="attendance-analytics-yearly-distribution-chart-container"', false);
        $response->assertSee('data-testid="attendance-analytics-pie-chart-container"', false);
    }

    public function test_summary_cards_tampil_dengan_angka_benar(): void
    {
        [$admin] = $this->makeAnalyticsContextWithAttendances();

        $response = $this->actingAs($admin)->get(route('attendances.analytics'));

        $response->assertOk();

        $monthSummary = $response->viewData('monthSummary');
        $yearSummary = $response->viewData('yearSummary');

        $this->assertSame(5, $monthSummary['total_attendances']);
        $this->assertSame(2, $monthSummary['status_counts']['present']);
        $this->assertSame(1, $monthSummary['status_counts']['leave']);
        $this->assertSame(1, $monthSummary['status_counts']['sick']);
        $this->assertSame(1, $monthSummary['status_counts']['absent']);
        $this->assertSame('18 jam 00 menit', $monthSummary['total_work_hours_label']);

        $this->assertSame(6, $yearSummary['total_attendances']);
        $this->assertSame(3, $yearSummary['status_counts']['present']);
        $this->assertSame(1, $yearSummary['status_counts']['leave']);
        $this->assertSame(1, $yearSummary['status_counts']['sick']);
        $this->assertSame(1, $yearSummary['status_counts']['absent']);
        $this->assertSame('26 jam 00 menit', $yearSummary['total_work_hours_label']);
    }

    public function test_chart_data_sesuai_agregasi(): void
    {
        [$admin] = $this->makeAnalyticsContextWithAttendances();

        $response = $this->actingAs($admin)->get(route('attendances.analytics'));

        $response->assertOk();

        $monthlyTrendChart = $response->viewData('monthlyTrendChart');
        $yearlyDistributionChart = $response->viewData('yearlyDistributionChart');
        $statusDistributionChart = $response->viewData('statusDistributionChart');

        $this->assertSame([
            'Mei 2025',
            'Jun 2025',
            'Jul 2025',
            'Agu 2025',
            'Sep 2025',
            'Okt 2025',
            'Nov 2025',
            'Des 2025',
            'Jan 2026',
            'Feb 2026',
            'Mar 2026',
            'Apr 2026',
        ], $monthlyTrendChart['labels']);

        $monthlyDatasets = collect($monthlyTrendChart['datasets'])->keyBy('label');
        $this->assertSame([0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 2], $monthlyDatasets['Hadir']['data']);
        $this->assertSame([0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 1], $monthlyDatasets['Izin']['data']);
        $this->assertSame([0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1], $monthlyDatasets['Sakit']['data']);
        $this->assertSame([0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 1], $monthlyDatasets['Alpha']['data']);

        $this->assertSame(['2022', '2023', '2024', '2025', '2026'], $yearlyDistributionChart['labels']);
        $yearlyDatasets = collect($yearlyDistributionChart['datasets'])->keyBy('label');
        $this->assertSame([1, 0, 0, 0, 3], $yearlyDatasets['Hadir']['data']);
        $this->assertSame([0, 0, 0, 1, 1], $yearlyDatasets['Izin']['data']);
        $this->assertSame([0, 0, 0, 1, 1], $yearlyDatasets['Sakit']['data']);
        $this->assertSame([0, 0, 0, 1, 1], $yearlyDatasets['Alpha']['data']);

        $this->assertSame(['Hadir', 'Izin', 'Sakit', 'Alpha'], array_values($statusDistributionChart['labels']));
        $this->assertSame([2, 1, 1, 1], $statusDistributionChart['counts']);
    }

    public function test_filter_bulan_dan_tahun_bekerja(): void
    {
        [$admin] = $this->makeAnalyticsContextWithAttendances();

        $response = $this->actingAs($admin)->get(route('attendances.analytics', [
            'year' => 2025,
            'month' => 12,
        ]));

        $response->assertOk();
        $response->assertSee('Periode Detail: Desember 2025');

        $this->assertSame(2025, $response->viewData('selectedYear'));
        $this->assertSame(12, $response->viewData('selectedMonth'));
        $this->assertSame('Desember', $response->viewData('selectedMonthLabel'));

        $monthSummary = $response->viewData('monthSummary');
        $yearSummary = $response->viewData('yearSummary');
        $monthlyTrendChart = $response->viewData('monthlyTrendChart');
        $yearlyDistributionChart = $response->viewData('yearlyDistributionChart');

        $this->assertSame(1, $monthSummary['total_attendances']);
        $this->assertSame(0, $monthSummary['status_counts']['present']);
        $this->assertSame(1, $monthSummary['status_counts']['leave']);
        $this->assertSame(0, $monthSummary['status_counts']['sick']);
        $this->assertSame(0, $monthSummary['status_counts']['absent']);

        $this->assertSame(3, $yearSummary['total_attendances']);
        $this->assertSame(['2021', '2022', '2023', '2024', '2025'], $yearlyDistributionChart['labels']);

        $monthlyDatasets = collect($monthlyTrendChart['datasets'])->keyBy('label');
        $this->assertSame(1, last($monthlyDatasets['Izin']['data']));
        $this->assertSame('Des 2025', last($monthlyTrendChart['labels']));
    }

    public function test_employee_tidak_bisa_mengakses_halaman_attendance_analytics(): void
    {
        [, $tenant, $employees] = $this->makeAnalyticsContextWithAttendances();

        $employeeUser = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Attendance Analytics Employee User',
            'email' => 'attendance-analytics-employee-user@example.test',
            'password' => 'password',
            'role' => 'employee',
            'status' => 'active',
        ]);

        $employees[0]->update([
            'user_id' => $employeeUser->id,
        ]);

        $response = $this->actingAs($employeeUser)->get(route('attendances.analytics'));

        $response->assertForbidden();
    }

    protected function makeAnalyticsContextWithAttendances(): array
    {
        $tenant = Tenant::create([
            'name' => 'Attendance Analytics Tenant',
            'slug' => 'attendance-analytics-tenant',
            'domain' => 'attendance-analytics-tenant.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Attendance Analytics Admin',
            'email' => 'attendance-analytics-admin@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $workLocation = WorkLocation::create([
            'tenant_id' => $tenant->id,
            'name' => 'Attendance Analytics Office',
            'address' => 'Jakarta',
            'latitude' => -6.2000000,
            'longitude' => 106.8166667,
            'radius' => 250,
        ]);

        $employees = collect(range(1, 5))->map(function (int $index) use ($tenant, $workLocation) {
            return Employee::create([
                'tenant_id' => $tenant->id,
                'work_location_id' => $workLocation->id,
                'employee_code' => 'ATX-00'.$index,
                'name' => 'Attendance Analytics Employee '.$index,
                'email' => 'attendance-analytics-employee-'.$index.'@example.test',
                'status' => 'active',
            ]);
        });

        Attendance::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employees[0]->id,
            'date' => '2026-04-01',
            'check_in' => '08:00',
            'check_out' => '17:00',
            'status' => 'present',
        ]);

        Attendance::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employees[1]->id,
            'date' => '2026-04-02',
            'check_in' => '08:30',
            'check_out' => '17:30',
            'status' => 'late',
        ]);

        Attendance::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employees[2]->id,
            'date' => '2026-04-03',
            'status' => 'leave',
        ]);

        Attendance::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employees[3]->id,
            'date' => '2026-04-04',
            'status' => 'sick',
        ]);

        Attendance::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employees[4]->id,
            'date' => '2026-04-05',
            'status' => 'absent',
        ]);

        Attendance::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employees[0]->id,
            'date' => '2026-01-15',
            'check_in' => '08:00',
            'check_out' => '16:00',
            'status' => 'present',
        ]);

        Attendance::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employees[1]->id,
            'date' => '2025-12-11',
            'status' => 'leave',
        ]);

        Attendance::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employees[2]->id,
            'date' => '2025-11-20',
            'status' => 'sick',
        ]);

        Attendance::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employees[3]->id,
            'date' => '2025-07-20',
            'status' => 'absent',
        ]);

        Attendance::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employees[4]->id,
            'date' => '2022-03-15',
            'check_in' => '09:00',
            'check_out' => '17:00',
            'status' => 'present',
        ]);

        return [$admin, $tenant, $employees];
    }
}