<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeavesAnalyticsDashboardTest extends TestCase
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

    public function test_route_leaves_analytics_accessible_by_admin(): void
    {
        [$admin, $manager, $employeeUser] = $this->makeContext();

        $adminResponse = $this->actingAs($admin)->get(route('leaves.analytics'));
        $managerResponse = $this->actingAs($manager)->get(route('leaves.analytics'));
        $employeeResponse = $this->actingAs($employeeUser)->get(route('leaves.analytics'));

        $adminResponse->assertOk();
        $adminResponse->assertViewIs('leaves.analytics');
        $managerResponse->assertOk();
        $employeeResponse->assertForbidden();
    }

    public function test_summary_cards_tampil_dengan_angka_benar(): void
    {
        [$admin] = $this->makeContext();

        $response = $this->actingAs($admin)->get(route('leaves.analytics'));

        $response->assertOk();
        $response->assertSee('Total Permintaan Bulan Ini');
        $response->assertSee('Total Hari Cuti Bulan Ini');
        $response->assertSee('Distribusi Pending');
        $response->assertSee('Distribusi Approved');
        $response->assertSee('Distribusi Rejected');
        $response->assertSee('data-testid="leave-analytics-card-total-requests-value"', false);
        $response->assertSee('data-testid="leave-analytics-card-total-days-value"', false);
        $response->assertSee('Pending: 1 / 1 hari');
        $response->assertSee('Approved: 1 / 2 hari');
        $response->assertSee('Rejected: 0 / 0 hari');

        $summary = $response->viewData('summary');

        $this->assertSame(2, $summary['total_requests']);
        $this->assertSame(3, $summary['total_days']);
        $this->assertSame(1, $summary['pending_count']);
        $this->assertSame(1, $summary['approved_count']);
        $this->assertSame(0, $summary['rejected_count']);
    }

    public function test_chart_data_sesuai_agregasi(): void
    {
        [$admin] = $this->makeContext();

        $response = $this->actingAs($admin)->get(route('leaves.analytics'));

        $response->assertOk();

        $monthly = $response->viewData('monthly');
        $annual = $response->viewData('annual');
        $charts = $response->viewData('charts');

        $this->assertCount(12, $monthly);
        $this->assertSame('Jan', $monthly[0]['period_label']);
        $this->assertSame(1, $monthly[0]['pending']['requests']);
        $this->assertSame(2, $monthly[0]['total_days']);
        $this->assertSame('Feb', $monthly[1]['period_label']);
        $this->assertSame(1, $monthly[1]['approved']['requests']);
        $this->assertSame(1, $monthly[1]['rejected']['requests']);
        $this->assertSame('Apr', $monthly[3]['period_label']);
        $this->assertSame(1, $monthly[3]['pending']['requests']);
        $this->assertSame(1, $monthly[3]['approved']['requests']);

        $this->assertSame(['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'], $charts['monthlyTrend']['labels']);
        $this->assertSame([1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0], $charts['monthlyTrend']['pending_requests']);
        $this->assertSame([0, 1, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0], $charts['monthlyTrend']['approved_requests']);
        $this->assertSame([0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0], $charts['monthlyTrend']['rejected_requests']);

        $this->assertCount(5, $annual);
        $this->assertSame(2022, $annual[0]['year']);
        $this->assertSame(0, $annual[0]['total_requests']);
        $this->assertSame(2024, $annual[2]['year']);
        $this->assertSame(1, $annual[2]['rejected']['requests']);
        $this->assertSame(2025, $annual[3]['year']);
        $this->assertSame(1, $annual[3]['approved']['requests']);
        $this->assertSame(2026, $annual[4]['year']);
        $this->assertSame(2, $annual[4]['pending']['requests']);
        $this->assertSame(2, $annual[4]['approved']['requests']);
        $this->assertSame(1, $annual[4]['rejected']['requests']);

        $this->assertSame(['2022', '2023', '2024', '2025', '2026'], $charts['annualStatus']['labels']);
        $this->assertSame([0, 0, 0, 0, 2], $charts['annualStatus']['pending_requests']);
        $this->assertSame([0, 0, 0, 1, 2], $charts['annualStatus']['approved_requests']);
        $this->assertSame([0, 0, 1, 0, 1], $charts['annualStatus']['rejected_requests']);
        $this->assertSame([1, 1, 0], $charts['statusPie']['requests']);
        $this->assertSame([50.0, 50.0, 0.0], $charts['statusPie']['percentages']);
    }

    public function test_filter_bulan_tahun_bekerja(): void
    {
        [$admin] = $this->makeContext();

        $response = $this->actingAs($admin)->get(route('leaves.analytics', [
            'year' => 2026,
            'month' => 2,
        ]));

        $response->assertOk();
        $response->assertSee('Periode aktif: Februari 2026');
        $response->assertSee('Pending: 0 / 0 hari');
        $response->assertSee('Approved: 1 / 3 hari');
        $response->assertSee('Rejected: 1 / 1 hari');

        $summary = $response->viewData('summary');
        $charts = $response->viewData('charts');

        $this->assertSame(2026, $response->viewData('selectedYear'));
        $this->assertSame(2, $response->viewData('selectedMonth'));
        $this->assertSame(2, $summary['total_requests']);
        $this->assertSame(4, $summary['total_days']);
        $this->assertSame([0, 1, 1], $charts['statusPie']['requests']);
        $this->assertSame([0.0, 50.0, 50.0], $charts['statusPie']['percentages']);
    }

    protected function makeContext(): array
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Analytics Dashboard',
            'slug' => 'tenant-analytics-dashboard',
            'domain' => 'tenant-analytics-dashboard.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Analytics Dashboard',
            'email' => 'admin-analytics-dashboard@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $manager = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Manager Analytics Dashboard',
            'email' => 'manager-analytics-dashboard@example.test',
            'password' => 'password',
            'role' => 'manager',
            'status' => 'active',
        ]);

        $employeeUser = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Employee Analytics Dashboard',
            'email' => 'employee-analytics-dashboard@example.test',
            'password' => 'password',
            'role' => 'employee',
            'status' => 'active',
        ]);

        $employeeA = Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $employeeUser->id,
            'employee_code' => 'LAD-001',
            'name' => 'Employee Analytics A',
            'email' => 'employee-analytics-a@example.test',
            'status' => 'active',
        ]);

        $employeeB = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'LAD-002',
            'name' => 'Employee Analytics B',
            'email' => 'employee-analytics-b@example.test',
            'status' => 'active',
        ]);

        $this->makeLeave($tenant, $employeeA, '2026-01-05', '2026-01-06', 'pending', 'Pending Januari');
        $this->makeLeave($tenant, $employeeA, '2026-02-10', '2026-02-12', 'approved', 'Approved Februari');
        $this->makeLeave($tenant, $employeeB, '2026-02-20', '2026-02-20', 'rejected', 'Rejected Februari');
        $this->makeLeave($tenant, $employeeA, '2026-04-03', '2026-04-03', 'pending', 'Pending April');
        $this->makeLeave($tenant, $employeeB, '2026-04-10', '2026-04-11', 'approved', 'Approved April');
        $this->makeLeave($tenant, $employeeA, '2025-08-14', '2025-08-17', 'approved', 'Approved 2025');
        $this->makeLeave($tenant, $employeeB, '2024-11-01', '2024-11-02', 'rejected', 'Rejected 2024');

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