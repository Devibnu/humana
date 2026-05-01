<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeavesAnomalyDashboardTest extends TestCase
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

    public function test_route_leaves_anomalies_accessible_by_admin(): void
    {
        [$admin, $manager, $employeeUser] = $this->makeContext();

        $this->actingAs($admin)->get(route('leaves.anomalies'))->assertOk()->assertViewIs('leaves.anomalies');
        $this->actingAs($manager)->get(route('leaves.anomalies'))->assertOk();
        $this->actingAs($employeeUser)->get(route('leaves.anomalies'))->assertForbidden();
    }

    public function test_summary_cards_tampil_dengan_angka_benar(): void
    {
        [$admin] = $this->makeContext();

        $response = $this->actingAs($admin)->get(route('leaves.anomalies'));

        $response->assertOk();
        $response->assertSee('Jumlah Anomali Terdeteksi Bulan Ini');
        $response->assertSee('Lonjakan');
        $response->assertSee('Pola Berulang');
        $response->assertSee('Carry-Over');
        $response->assertSee('data-testid="leave-anomaly-total-value"', false);

        $summary = $response->viewData('summary');

        $this->assertSame(2, $summary['anomalies_this_month']);
        $this->assertSame(1, $summary['spike_count']);
        $this->assertSame(1, $summary['recurring_count']);
        $this->assertSame(1, $summary['carry_over_count']);
        $this->assertSame(3, $summary['total_alerts']);
    }

    public function test_chart_data_sesuai_analisis(): void
    {
        [$admin] = $this->makeContext();

        $response = $this->actingAs($admin)->get(route('leaves.anomalies'));

        $response->assertOk();

        $charts = $response->viewData('charts');

        $this->assertSame(['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'], $charts['spikeTrend']['labels']);
        $this->assertSame([15, 3, 3, 8, 1, 0, 0, 0, 0, 0, 0, 0], $charts['spikeTrend']['days']);
        $this->assertSame([15, null, null, 8, null, null, null, null, null, null, null, null], $charts['spikeTrend']['anomaly_points']);
        $this->assertSame(['2022', '2023', '2024', '2025', '2026'], $charts['carryOver']['labels']);
        $this->assertSame([0, 0, 0, 0, 12], $charts['carryOver']['days']);
        $this->assertSame(1, $charts['heatmap']['matrix'][4][0]);
        $this->assertSame(1, $charts['heatmap']['matrix'][4][1]);
        $this->assertSame(1, $charts['heatmap']['matrix'][4][2]);
        $this->assertSame(1, $charts['heatmap']['matrix'][4][3]);
        $this->assertSame(1, $charts['heatmap']['matrix'][4][4]);
    }

    public function test_alert_list_muncul_sesuai_deteksi(): void
    {
        [$admin] = $this->makeContext();

        $response = $this->actingAs($admin)->get(route('leaves.anomalies'));

        $response->assertOk();
        $response->assertSee('data-testid="leave-anomaly-alert-list"', false);
        $response->assertSee('Employee Lonjakan → Lonjakan cuti bulan April');
        $response->assertSee('Employee Lonjakan mengalami lonjakan cuti bulan April (7 hari vs rata-rata 2.0 hari).');
        $response->assertSee('Employee Pola → Pola berulang hari Jumat');
        $response->assertSee('Employee Pola menunjukkan pola berulang: cuti di hari Jumat 5x berturut-turut.');
        $response->assertSee('Employee Carry → Carry-over cuti tinggi');
        $response->assertSee('Employee Carry memiliki indikasi carry-over cuti 12 hari dari tahun lalu.');
    }

    protected function makeContext(): array
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Leave Anomaly Dashboard',
            'slug' => 'tenant-leave-anomaly-dashboard',
            'domain' => 'tenant-leave-anomaly-dashboard.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Leave Anomaly',
            'email' => 'admin-leave-anomaly@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $manager = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Manager Leave Anomaly',
            'email' => 'manager-leave-anomaly@example.test',
            'password' => 'password',
            'role' => 'manager',
            'status' => 'active',
        ]);

        $employeeUser = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Employee Leave Anomaly',
            'email' => 'employee-leave-anomaly@example.test',
            'password' => 'password',
            'role' => 'employee',
            'status' => 'active',
        ]);

        $employeeSpike = Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $employeeUser->id,
            'employee_code' => 'LAN-001',
            'name' => 'Employee Lonjakan',
            'email' => 'employee-lonjakan@example.test',
            'status' => 'active',
        ]);

        $employeePattern = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'LAN-002',
            'name' => 'Employee Pola',
            'email' => 'employee-pola@example.test',
            'status' => 'active',
        ]);

        $employeeCarry = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'LAN-003',
            'name' => 'Employee Carry',
            'email' => 'employee-carry@example.test',
            'status' => 'active',
        ]);

        $this->makeLeave($tenant, $employeeSpike, '2026-01-06', '2026-01-07', 'approved', 'Baseline Januari');
        $this->makeLeave($tenant, $employeeSpike, '2026-02-10', '2026-02-11', 'approved', 'Baseline Februari');
        $this->makeLeave($tenant, $employeeSpike, '2026-03-03', '2026-03-04', 'approved', 'Baseline Maret');
        $this->makeLeave($tenant, $employeeSpike, '2026-04-01', '2026-04-07', 'approved', 'Lonjakan April');

        $this->makeLeave($tenant, $employeePattern, '2026-01-02', '2026-01-02', 'pending', 'Jumat 1');
        $this->makeLeave($tenant, $employeePattern, '2026-02-06', '2026-02-06', 'pending', 'Jumat 2');
        $this->makeLeave($tenant, $employeePattern, '2026-03-06', '2026-03-06', 'pending', 'Jumat 3');
        $this->makeLeave($tenant, $employeePattern, '2026-04-03', '2026-04-03', 'pending', 'Jumat 4');
        $this->makeLeave($tenant, $employeePattern, '2026-05-01', '2026-05-01', 'pending', 'Jumat 5');

        $this->makeLeave($tenant, $employeeCarry, '2025-01-05', '2025-01-10', 'approved', 'Carry 2025');
        $this->makeLeave($tenant, $employeeCarry, '2026-01-12', '2026-01-23', 'approved', 'Carry 2026');

        return [$admin, $manager, $employeeUser, $tenant];
    }

    protected function makeLeave(Tenant $tenant, Employee $employee, string $startDate, string $endDate, string $status, string $reason): Leave
    {
        return Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => $reason,
            'status' => $status,
        ]);
    }
}