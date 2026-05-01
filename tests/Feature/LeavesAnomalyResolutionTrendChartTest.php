<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeavesAnomalyResolutionTrendChartTest extends TestCase
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

    public function test_route_leaves_anomalies_resolutions_trends_accessible_by_admin(): void
    {
        [$admin, $manager, $employeeUser] = $this->makeContext();

        $this->actingAs($admin)->get(route('leaves.anomalies.resolutions.trends'))->assertOk()->assertViewIs('leaves.anomalies.resolutions.trends');
        $this->actingAs($manager)->get(route('leaves.anomalies.resolutions.trends'))->assertOk();
        $this->actingAs($employeeUser)->get(route('leaves.anomalies.resolutions.trends'))->assertForbidden();
    }

    public function test_line_chart_tampil_dengan_data_bulanan(): void
    {
        [$admin] = $this->makeContext();

        $response = $this->actingAs($admin)->get(route('leaves.anomalies.resolutions.trends'));

        $response->assertOk();
        $response->assertSee('data-testid="leave-anomaly-resolution-trend-line-chart-container"', false);

        $monthly = $response->viewData('monthly');
        $summary = $response->viewData('summary');

        $this->assertCount(12, $monthly);
        $this->assertSame('Mei 2025', $monthly[0]['label']);
        $this->assertSame('Apr 2026', $monthly[11]['label']);
        $this->assertSame(0, $monthly[10]['resolved']);
        $this->assertSame(0, $monthly[10]['unresolved']);
        $this->assertSame(2, $monthly[11]['resolved']);
        $this->assertSame(1, $monthly[11]['unresolved']);
        $this->assertSame(2, $summary['resolved_this_month']);
        $this->assertSame(1, $summary['unresolved_active']);
    }

    public function test_bar_chart_tampil_dengan_data_tahunan(): void
    {
        [$admin] = $this->makeContext();

        $response = $this->actingAs($admin)->get(route('leaves.anomalies.resolutions.trends'));

        $response->assertOk();
        $response->assertSee('data-testid="leave-anomaly-resolution-trend-bar-chart-container"', false);

        $annual = $response->viewData('annual');

        $this->assertCount(5, $annual);
        $this->assertSame(2022, $annual[0]['year']);
        $this->assertSame(2026, $annual[4]['year']);
        $this->assertSame(1, $annual[3]['teguran']);
        $this->assertSame(1, $annual[4]['disetujui_khusus']);
        $this->assertSame(1, $annual[4]['investigasi']);
        $this->assertSame(2, $response->viewData('summary')['resolved_this_year']);
    }

    public function test_pie_chart_tampil_dengan_distribusi_tindakan(): void
    {
        [$admin] = $this->makeContext();

        $response = $this->actingAs($admin)->get(route('leaves.anomalies.resolutions.trends'));

        $response->assertOk();
        $response->assertSee('data-testid="leave-anomaly-resolution-trend-pie-chart-container"', false);

        $actions = collect($response->viewData('actions'))->keyBy('label');

        $this->assertSame(1, $actions['Investigasi']['total']);
        $this->assertSame(50.0, $actions['Investigasi']['percentage']);
        $this->assertSame(0, $actions['Teguran']['total']);
        $this->assertSame(1, $actions['Disetujui Khusus']['total']);
        $this->assertSame(50.0, $actions['Disetujui Khusus']['percentage']);
        $this->assertSame(0, $actions['Abaikan']['total']);
    }

    public function test_filter_tahun_bekerja(): void
    {
        [$admin] = $this->makeContext();

        $response = $this->actingAs($admin)->get(route('leaves.anomalies.resolutions.trends', ['year' => 2025]));

        $response->assertOk();
        $response->assertSee('data-testid="leave-anomaly-resolution-trend-year-filter"', false);

        $monthly = $response->viewData('monthly');
        $annual = $response->viewData('annual');
        $actions = collect($response->viewData('actions'))->keyBy('label');

        $this->assertSame(2025, $response->viewData('selectedYear'));
        $this->assertSame('Jan 2025', $monthly[0]['label']);
        $this->assertSame('Des 2025', $monthly[11]['label']);
        $this->assertSame(1, $annual[4]['teguran']);
        $this->assertSame(1, $response->viewData('summary')['resolved_this_year']);
        $this->assertSame(1, $actions['Teguran']['total']);
    }

    protected function makeContext(): array
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Leave Anomaly Resolution Trends',
            'slug' => 'tenant-leave-anomaly-resolution-trends',
            'domain' => 'tenant-leave-anomaly-resolution-trends.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Leave Anomaly Resolution Trends',
            'email' => 'admin-leave-anomaly-resolution-trends@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $manager = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Manager Leave Anomaly Resolution Trends',
            'email' => 'manager-leave-anomaly-resolution-trends@example.test',
            'password' => 'password',
            'role' => 'manager',
            'status' => 'active',
        ]);

        $employeeUser = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Employee Leave Anomaly Resolution Trends',
            'email' => 'employee-leave-anomaly-resolution-trends@example.test',
            'password' => 'password',
            'role' => 'employee',
            'status' => 'active',
        ]);

        $employeeSpike = Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $employeeUser->id,
            'employee_code' => 'LRT-001',
            'name' => 'Employee Lonjakan',
            'email' => 'employee-lonjakan-resolution-trends@example.test',
            'status' => 'active',
        ]);

        $employeePattern = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'LRT-002',
            'name' => 'Employee Pola',
            'email' => 'employee-pola-resolution-trends@example.test',
            'status' => 'active',
        ]);

        $employeeCarry = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'LRT-003',
            'name' => 'Employee Carry',
            'email' => 'employee-carry-resolution-trends@example.test',
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

        $this->makeLeave($tenant, $employeeCarry, '2025-01-05', '2025-01-10', 'approved', 'Carry 2025 Januari');
        $this->makeLeave($tenant, $employeeCarry, '2025-02-03', '2025-02-08', 'approved', 'Carry 2025 Februari');
        $this->makeLeave($tenant, $employeeCarry, '2026-01-12', '2026-01-23', 'approved', 'Carry 2026');

        Carbon::setTestNow('2025-04-22 10:00:00');
        $this->actingAs($manager)->get(route('leaves.anomalies'))->assertOk();
        $carryNotification = $manager->fresh()->notifications
            ->first(fn ($item) => data_get($item->data, 'employee_name') === 'Employee Carry' && data_get($item->data, 'category') === 'leave_anomaly');
        $this->actingAs($manager)->post(route('leaves.anomalies.resolve', $carryNotification->id), [
            'resolution_note' => 'Perlu teguran administratif untuk pengajuan yang terus berulang.',
            'resolution_action' => 'Teguran',
        ])->assertRedirect();

        Carbon::setTestNow('2026-04-22 10:00:00');
        $this->actingAs($manager)->get(route('leaves.anomalies'))->assertOk();

        $spikeNotification = $manager->fresh()->notifications
            ->first(fn ($item) => data_get($item->data, 'employee_name') === 'Employee Lonjakan' && data_get($item->data, 'category') === 'leave_anomaly');
        $this->actingAs($manager)->post(route('leaves.anomalies.resolve', $spikeNotification->id), [
            'resolution_note' => 'Sudah ditindaklanjuti dan disetujui karena kebutuhan khusus keluarga.',
            'resolution_action' => 'Disetujui Khusus',
        ])->assertRedirect();

        $patternNotification = $manager->fresh()->notifications
            ->first(fn ($item) => data_get($item->data, 'employee_name') === 'Employee Pola' && data_get($item->data, 'category') === 'leave_anomaly');
        $this->actingAs($manager)->post(route('leaves.anomalies.resolve', $patternNotification->id), [
            'resolution_note' => 'Butuh investigasi lanjutan oleh HR dan atasan langsung.',
            'resolution_action' => 'Investigasi',
        ])->assertRedirect();

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