<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeavesAnomalyTrendChartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-04-22 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_route_leaves_anomalies_trends_accessible_by_admin(): void
    {
        [$admin, $manager, $employeeUser] = $this->makeContext();

        $this->actingAs($admin)->get(route('leaves.anomalies.trends'))->assertOk()->assertViewIs('leaves.anomalies.trends');
        $this->actingAs($manager)->get(route('leaves.anomalies.trends'))->assertOk();
        $this->actingAs($employeeUser)->get(route('leaves.anomalies.trends'))->assertForbidden();
    }

    public function test_line_chart_tampil_dengan_data_bulanan(): void
    {
        [$admin] = $this->makeContext();

        $response = $this->actingAs($admin)->get(route('leaves.anomalies.trends'));

        $response->assertOk();
        $response->assertSee('data-testid="leave-anomaly-trend-line-chart-container"', false);

        $monthly = $response->viewData('monthly');
        $charts = $response->viewData('charts');

        $this->assertCount(12, $monthly);
        $this->assertSame(['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'], $charts['monthlyTrend']['labels']);
        $this->assertSame([0, 0, 0, 1, 0, 0, 0, 0, 0, 1, 0, 0], $charts['monthlyTrend']['spike']);
        $this->assertSame([1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0], $charts['monthlyTrend']['recurring']);
        $this->assertSame([1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0], $charts['monthlyTrend']['carry_over']);
        $this->assertSame([2, 2, 1, 2, 1, 0, 0, 0, 0, 1, 0, 0], $charts['monthlyTrend']['totals']);
        $this->assertSame(2, $response->viewData('summary')['total_this_month']);
    }

    public function test_bar_chart_tampil_dengan_data_tahunan(): void
    {
        [$admin] = $this->makeContext();

        $response = $this->actingAs($admin)->get(route('leaves.anomalies.trends'));

        $response->assertOk();
        $response->assertSee('data-testid="leave-anomaly-trend-bar-chart-container"', false);

        $annual = $response->viewData('annual');
        $charts = $response->viewData('charts');

        $this->assertCount(5, $annual);
        $this->assertSame(['2022', '2023', '2024', '2025', '2026'], $charts['annualTrend']['labels']);
        $this->assertSame([0, 0, 0, 0, 2], $charts['annualTrend']['spike']);
        $this->assertSame([0, 0, 0, 0, 1], $charts['annualTrend']['recurring']);
        $this->assertSame([0, 0, 0, 1, 1], $charts['annualTrend']['carry_over']);
        $this->assertSame([0, 0, 0, 1, 4], $charts['annualTrend']['totals']);
        $this->assertSame(4, $response->viewData('summary')['total_this_year']);
    }

    public function test_filter_tahun_bekerja(): void
    {
        [$admin] = $this->makeContext();

        $response = $this->actingAs($admin)->get(route('leaves.anomalies.trends', ['year' => 2025]));

        $response->assertOk();
        $response->assertSee('data-testid="leave-anomaly-trend-year-filter"', false);

        $charts = $response->viewData('charts');

        $this->assertSame(2025, $response->viewData('selectedYear'));
        $this->assertSame([0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0], $charts['monthlyTrend']['spike']);
        $this->assertSame([0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0], $charts['monthlyTrend']['recurring']);
        $this->assertSame([1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0], $charts['monthlyTrend']['carry_over']);
        $this->assertSame(['2021', '2022', '2023', '2024', '2025'], $charts['annualTrend']['labels']);
        $this->assertSame([0, 0, 0, 0, 0], $charts['annualTrend']['spike']);
        $this->assertSame([0, 0, 0, 0, 0], $charts['annualTrend']['recurring']);
        $this->assertSame([0, 0, 0, 0, 1], $charts['annualTrend']['carry_over']);
    }

    protected function makeContext(): array
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Leave Anomaly Trends',
            'slug' => 'tenant-leave-anomaly-trends',
            'domain' => 'tenant-leave-anomaly-trends.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Leave Anomaly Trends',
            'email' => 'admin-leave-anomaly-trends@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $manager = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Manager Leave Anomaly Trends',
            'email' => 'manager-leave-anomaly-trends@example.test',
            'password' => 'password',
            'role' => 'manager',
            'status' => 'active',
        ]);

        $employeeUser = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Employee Leave Anomaly Trends',
            'email' => 'employee-leave-anomaly-trends@example.test',
            'password' => 'password',
            'role' => 'employee',
            'status' => 'active',
        ]);

        $employeeSpike = Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $employeeUser->id,
            'employee_code' => 'LAT-001',
            'name' => 'Employee Lonjakan',
            'email' => 'employee-lonjakan-trends@example.test',
            'status' => 'active',
        ]);

        $employeePattern = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'LAT-002',
            'name' => 'Employee Pola',
            'email' => 'employee-pola-trends@example.test',
            'status' => 'active',
        ]);

        $employeeSpikeLate = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'LAT-004',
            'name' => 'Employee Lonjakan Akhir Tahun',
            'email' => 'employee-lonjakan-akhir-tahun@example.test',
            'status' => 'active',
        ]);

        $employeeCarry = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'LAT-003',
            'name' => 'Employee Carry',
            'email' => 'employee-carry-trends@example.test',
            'status' => 'active',
        ]);

        $this->makeLeave($tenant, $employeeSpike, '2026-01-06', '2026-01-07', 'approved', 'Baseline Januari');
        $this->makeLeave($tenant, $employeeSpike, '2026-02-10', '2026-02-11', 'approved', 'Baseline Februari');
        $this->makeLeave($tenant, $employeeSpike, '2026-03-03', '2026-03-04', 'approved', 'Baseline Maret');
        $this->makeLeave($tenant, $employeeSpike, '2026-04-01', '2026-04-10', 'approved', 'Lonjakan April');

        $this->makeLeave($tenant, $employeePattern, '2026-01-02', '2026-01-02', 'pending', 'Jumat 1');
        $this->makeLeave($tenant, $employeePattern, '2026-02-06', '2026-02-06', 'pending', 'Jumat 2');
        $this->makeLeave($tenant, $employeePattern, '2026-03-06', '2026-03-06', 'pending', 'Jumat 3');
        $this->makeLeave($tenant, $employeePattern, '2026-04-03', '2026-04-03', 'pending', 'Jumat 4');
        $this->makeLeave($tenant, $employeePattern, '2026-05-01', '2026-05-01', 'pending', 'Jumat 5');

        $this->makeLeave($tenant, $employeeSpikeLate, '2026-01-08', '2026-01-08', 'approved', 'Baseline Januari 2');
        $this->makeLeave($tenant, $employeeSpikeLate, '2026-02-11', '2026-02-11', 'approved', 'Baseline Februari 2');
        $this->makeLeave($tenant, $employeeSpikeLate, '2026-03-10', '2026-03-10', 'approved', 'Baseline Maret 2');
        $this->makeLeave($tenant, $employeeSpikeLate, '2026-10-01', '2026-10-04', 'approved', 'Lonjakan Oktober');

        $this->makeLeave($tenant, $employeeCarry, '2025-01-05', '2025-01-16', 'approved', 'Carry 2025');
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