<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeavesAnomalyResolutionDashboardTest extends TestCase
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

    public function test_route_leaves_anomalies_resolutions_accessible_by_admin(): void
    {
        [$admin, $manager, $employeeUser] = $this->makeContext();

        $this->actingAs($admin)->get(route('leaves.anomalies.resolutions'))->assertOk()->assertViewIs('leaves.anomalies.resolutions');
        $this->actingAs($manager)->get(route('leaves.anomalies.resolutions'))->assertOk();
        $this->actingAs($employeeUser)->get(route('leaves.anomalies.resolutions'))->assertForbidden();
    }

    public function test_summary_cards_tampil_dengan_angka_benar(): void
    {
        [$admin] = $this->makeContext();

        $response = $this->actingAs($admin)->get(route('leaves.anomalies.resolutions'));

        $response->assertOk();
        $response->assertSee('Jumlah Anomali Unresolved');
        $response->assertSee('Jumlah Anomali Resolved');
        $response->assertSee('Distribusi Jenis Anomali');

        $summary = $response->viewData('summary');

        $this->assertSame(2, $summary['unresolved']);
        $this->assertSame(1, $summary['resolved']);
        $this->assertSame(1, $summary['spike']);
        $this->assertSame(1, $summary['recurring']);
        $this->assertSame(1, $summary['carry_over']);
    }

    public function test_tabel_resolusi_menampilkan_data_sesuai_db(): void
    {
        [$admin] = $this->makeContext();

        $response = $this->actingAs($admin)->get(route('leaves.anomalies.resolutions'));

        $response->assertOk();
        $response->assertSee('data-testid="leave-anomaly-resolution-table-container"', false);
        $response->assertSee('Employee Lonjakan');
        $response->assertSee('Employee Pola');
        $response->assertSee('Employee Carry');
        $response->assertSee('Resolved');
        $response->assertSee('Disetujui Khusus');
        $response->assertSee('Sudah ditindaklanjuti dan disetujui karena kebutuhan khusus keluarga.');
        $response->assertSee('Resolve');
        $response->assertSee('Detail Resolusi');
    }

    public function test_filter_tenant_bulan_tahun_bekerja(): void
    {
        [$admin, , , $tenantA, $tenantB] = $this->makeContext();

        $response = $this->actingAs($admin)->get(route('leaves.anomalies.resolutions', [
            'tenant_id' => $tenantB->id,
            'month' => 3,
            'year' => 2025,
        ]));

        $response->assertOk();
        $response->assertSee('Employee Tenant Dua');
        $response->assertDontSee('Employee Lonjakan');
        $response->assertSee('data-testid="leave-anomaly-resolution-tenant-filter"', false);
        $response->assertSee('data-testid="leave-anomaly-resolution-month-filter"', false);
        $response->assertSee('data-testid="leave-anomaly-resolution-year-filter"', false);

        $summary = $response->viewData('summary');

        $this->assertSame(1, $summary['unresolved']);
        $this->assertSame(0, $summary['resolved']);
        $this->assertSame(1, $summary['spike']);
        $this->assertSame(0, $summary['recurring']);
        $this->assertSame(0, $summary['carry_over']);
    }

    protected function makeContext(): array
    {
        $tenantA = Tenant::create([
            'name' => 'Tenant Leave Anomaly Resolution Dashboard A',
            'slug' => 'tenant-leave-anomaly-resolution-dashboard-a',
            'domain' => 'tenant-leave-anomaly-resolution-dashboard-a.test',
            'status' => 'active',
        ]);

        $tenantB = Tenant::create([
            'name' => 'Tenant Leave Anomaly Resolution Dashboard B',
            'slug' => 'tenant-leave-anomaly-resolution-dashboard-b',
            'domain' => 'tenant-leave-anomaly-resolution-dashboard-b.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenantA->id,
            'name' => 'Admin Leave Anomaly Resolution Dashboard',
            'email' => 'admin-leave-anomaly-resolution-dashboard@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $manager = User::create([
            'tenant_id' => $tenantA->id,
            'name' => 'Manager Leave Anomaly Resolution Dashboard',
            'email' => 'manager-leave-anomaly-resolution-dashboard@example.test',
            'password' => 'password',
            'role' => 'manager',
            'status' => 'active',
        ]);

        $employeeUser = User::create([
            'tenant_id' => $tenantA->id,
            'name' => 'Employee Leave Anomaly Resolution Dashboard',
            'email' => 'employee-leave-anomaly-resolution-dashboard@example.test',
            'password' => 'password',
            'role' => 'employee',
            'status' => 'active',
        ]);

        $managerTenantB = User::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Manager Leave Anomaly Resolution Dashboard Tenant B',
            'email' => 'manager-leave-anomaly-resolution-dashboard-b@example.test',
            'password' => 'password',
            'role' => 'manager',
            'status' => 'active',
        ]);

        $employeeSpike = Employee::create([
            'tenant_id' => $tenantA->id,
            'user_id' => $employeeUser->id,
            'employee_code' => 'LRD-001',
            'name' => 'Employee Lonjakan',
            'email' => 'employee-lonjakan-resolution-dashboard@example.test',
            'status' => 'active',
        ]);

        $employeePattern = Employee::create([
            'tenant_id' => $tenantA->id,
            'employee_code' => 'LRD-002',
            'name' => 'Employee Pola',
            'email' => 'employee-pola-resolution-dashboard@example.test',
            'status' => 'active',
        ]);

        $employeeCarry = Employee::create([
            'tenant_id' => $tenantA->id,
            'employee_code' => 'LRD-003',
            'name' => 'Employee Carry',
            'email' => 'employee-carry-resolution-dashboard@example.test',
            'status' => 'active',
        ]);

        $employeeTenantB = Employee::create([
            'tenant_id' => $tenantB->id,
            'employee_code' => 'LRD-101',
            'name' => 'Employee Tenant Dua',
            'email' => 'employee-tenant-dua-resolution-dashboard@example.test',
            'status' => 'active',
        ]);

        $this->makeLeave($tenantA, $employeeSpike, '2026-01-06', '2026-01-07', 'approved', 'Baseline Januari');
        $this->makeLeave($tenantA, $employeeSpike, '2026-02-10', '2026-02-11', 'approved', 'Baseline Februari');
        $this->makeLeave($tenantA, $employeeSpike, '2026-03-03', '2026-03-04', 'approved', 'Baseline Maret');
        $this->makeLeave($tenantA, $employeeSpike, '2026-04-01', '2026-04-07', 'approved', 'Lonjakan April');

        $this->makeLeave($tenantA, $employeePattern, '2026-01-02', '2026-01-02', 'pending', 'Jumat 1');
        $this->makeLeave($tenantA, $employeePattern, '2026-02-06', '2026-02-06', 'pending', 'Jumat 2');
        $this->makeLeave($tenantA, $employeePattern, '2026-03-06', '2026-03-06', 'pending', 'Jumat 3');
        $this->makeLeave($tenantA, $employeePattern, '2026-04-03', '2026-04-03', 'pending', 'Jumat 4');
        $this->makeLeave($tenantA, $employeePattern, '2026-05-01', '2026-05-01', 'pending', 'Jumat 5');

        $this->makeLeave($tenantA, $employeeCarry, '2025-01-05', '2025-01-10', 'approved', 'Carry 2025');
        $this->makeLeave($tenantA, $employeeCarry, '2026-01-12', '2026-01-23', 'approved', 'Carry 2026');

        $this->makeLeave($tenantB, $employeeTenantB, '2025-01-06', '2025-01-06', 'approved', 'Baseline Januari B');
        $this->makeLeave($tenantB, $employeeTenantB, '2025-02-06', '2025-02-06', 'approved', 'Baseline Februari B');
        $this->makeLeave($tenantB, $employeeTenantB, '2025-03-01', '2025-03-08', 'approved', 'Lonjakan Maret B');

        $this->actingAs($manager)->get(route('leaves.anomalies'))->assertOk();

        $notification = $manager->fresh()->notifications
            ->first(fn ($item) => data_get($item->data, 'employee_name') === 'Employee Lonjakan' && data_get($item->data, 'category') === 'leave_anomaly');

        $this->actingAs($manager)->post(route('leaves.anomalies.resolve', $notification->id), [
            'resolution_note' => 'Sudah ditindaklanjuti dan disetujui karena kebutuhan khusus keluarga.',
            'resolution_action' => 'Disetujui Khusus',
        ])->assertRedirect();

        Carbon::setTestNow('2025-03-20 10:00:00');
        $this->actingAs($managerTenantB)->get(route('leaves.anomalies'))->assertOk();
        Carbon::setTestNow('2026-04-22 10:00:00');

        return [$admin, $manager, $employeeUser, $tenantA, $tenantB];
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