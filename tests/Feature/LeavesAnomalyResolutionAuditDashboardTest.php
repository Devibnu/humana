<?php

namespace Tests\Feature;

use App\Exports\LeavesAnomalyResolutionAuditDashboardExport;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Tenant;
use App\Models\User;
use App\Services\LeavesAnomalyService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class LeavesAnomalyResolutionAuditDashboardTest extends TestCase
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

    public function test_route_leaves_anomalies_resolutions_audit_accessible_by_admin(): void
    {
        [$admin, $manager, $employeeUser] = $this->makeContext();

        $this->actingAs($admin)->get(route('leaves.anomalies.resolutions.audit'))->assertOk()->assertViewIs('leaves.anomalies.resolutions.audit');
        $this->actingAs($manager)->get(route('leaves.anomalies.resolutions.audit'))->assertOk();
        $this->actingAs($employeeUser)->get(route('leaves.anomalies.resolutions.audit'))->assertForbidden();
    }

    public function test_summary_cards_tampil_dengan_angka_benar(): void
    {
        [$admin] = $this->makeContext();

        $response = $this->actingAs($admin)->get(route('leaves.anomalies.resolutions.audit'));
        $summary = $response->viewData('summary');

        $this->assertSame(2, $summary['resolved_this_month']);
        $this->assertSame(2, $summary['resolved_this_year']);
        $this->assertSame(1, $summary['unresolved_active']);
    }

    public function test_chart_data_sesuai_agregasi(): void
    {
        [$admin] = $this->makeContext();

        $response = $this->actingAs($admin)->get(route('leaves.anomalies.resolutions.audit'));

        $response->assertSee('data-testid="leave-anomaly-resolution-audit-dashboard-line-chart-container"', false);
        $response->assertSee('data-testid="leave-anomaly-resolution-audit-dashboard-bar-chart-container"', false);
        $response->assertSee('data-testid="leave-anomaly-resolution-audit-dashboard-pie-chart-container"', false);

        $charts = $response->viewData('charts');

        $this->assertSame('Apr 2026', $charts['monthlyTrend']['labels'][11]);
        $this->assertSame(2, $charts['monthlyTrend']['resolved'][11]);
        $this->assertSame(1, $charts['monthlyTrend']['unresolved'][11]);
        $this->assertSame(['2022', '2023', '2024', '2025', '2026'], $charts['annualTrend']['labels']);
        $this->assertSame(1, $charts['annualTrend']['investigasi'][4]);
        $this->assertSame(1, $charts['annualTrend']['disetujui_khusus'][4]);
        $this->assertSame(1, $charts['actionDistribution']['values'][0]);
    }

    public function test_tabel_log_menampilkan_data_resolusi_sesuai_db(): void
    {
        [$admin] = $this->makeContext();

        $response = $this->actingAs($admin)->get(route('leaves.anomalies.resolutions.audit'));

        $response->assertSee('data-testid="leave-anomaly-resolution-audit-dashboard-table-container"', false);
        $response->assertSee('Employee Lonjakan');
        $response->assertSee('Investigasi');
        $response->assertSee('Audit dashboard untuk lonjakan April sudah diverifikasi HR.');
    }

    public function test_filter_tenant_bulan_tahun_bekerja(): void
    {
        [$admin, , , $tenantA, $tenantB] = $this->makeContext();

        $response = $this->actingAs($admin)->get(route('leaves.anomalies.resolutions.audit', [
            'tenant_id' => $tenantB->id,
            'month' => 3,
            'year' => 2025,
        ]));

        $response->assertSee('Employee Tenant Dua');
        $response->assertDontSee('Employee Lonjakan');
        $response->assertSee('data-testid="leave-anomaly-resolution-audit-dashboard-tenant-filter"', false);
        $response->assertSee('data-testid="leave-anomaly-resolution-audit-dashboard-month-filter"', false);
        $response->assertSee('data-testid="leave-anomaly-resolution-audit-dashboard-year-filter"', false);
    }

    public function test_export_tombol_menghasilkan_file_dengan_data_dashboard(): void
    {
        Excel::fake();
        [$admin, , $employeeUser, $tenantA] = $this->makeContext();

        $response = $this->actingAs($admin)->get(route('leaves.anomalies.resolutions.audit'));
        $response->assertSee('data-testid="leave-anomaly-resolution-audit-dashboard-export-pdf"', false);
        $response->assertSee('data-testid="leave-anomaly-resolution-audit-dashboard-export-xlsx"', false);

        $this->actingAs($employeeUser)
            ->get(route('leaves.anomalies.resolutions.audit.export.xlsx', ['tenant_id' => $tenantA->id]))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('leaves.anomalies.resolutions.audit.export.xlsx', ['tenant_id' => $tenantA->id]))
            ->assertOk();

        Excel::assertDownloaded('leaves_anomaly_resolution_audit_dashboard_tenant-leave-anomaly-resolution-audit-dashboard-a_20260422.xlsx', function (LeavesAnomalyResolutionAuditDashboardExport $export) {
            $rows = array_map('array_values', $export->collection()->toArray());

            $this->assertContains(['Resolusi Bulan Ini', 2, 'Resolusi Tahun Ini', 2, 'Unresolved Aktif', 1, 'Total Log', 2], $rows);
            $this->assertContains([
                'Employee Lonjakan',
                'Lonjakan',
                'Employee Lonjakan mengalami lonjakan cuti bulan April (7 hari vs rata-rata 2.0 hari).',
                'April 2026',
                'Manager Leave Anomaly Resolution Audit Dashboard',
                'Investigasi',
                'Audit dashboard untuk lonjakan April sudah diverifikasi HR.',
                '22 Apr 2026 10:00',
            ], $rows);

            return true;
        });

        $pdfResponse = $this->actingAs($admin)->get(route('leaves.anomalies.resolutions.audit.export.pdf', ['tenant_id' => $tenantA->id]));
        $pdfResponse->assertOk();
        $pdfResponse->assertHeader('Content-Type', 'application/pdf');
        $pdfResponse->assertHeader('Content-Disposition', 'attachment; filename="leaves_anomaly_resolution_audit_dashboard_tenant-leave-anomaly-resolution-audit-dashboard-a_20260422.pdf"');

        $payload = app(LeavesAnomalyService::class)->buildResolutionAuditDashboardExportPayload($admin, $tenantA->id, 4, 2026, null);
        $rendered = view('leaves.exports.anomaly-resolution-audit-dashboard-pdf', $payload)->render();

        $this->assertStringContainsString('Audit Dashboard Resolusi Anomali Cuti', $rendered);
        $this->assertStringContainsString('Employee Lonjakan', $rendered);
        $this->assertStringContainsString('Audit dashboard untuk lonjakan April sudah diverifikasi HR.', $rendered);
    }

    protected function makeContext(): array
    {
        $tenantA = Tenant::create([
            'name' => 'Tenant Leave Anomaly Resolution Audit Dashboard A',
            'slug' => 'tenant-leave-anomaly-resolution-audit-dashboard-a',
            'domain' => 'tenant-leave-anomaly-resolution-audit-dashboard-a.test',
            'status' => 'active',
        ]);

        $tenantB = Tenant::create([
            'name' => 'Tenant Leave Anomaly Resolution Audit Dashboard B',
            'slug' => 'tenant-leave-anomaly-resolution-audit-dashboard-b',
            'domain' => 'tenant-leave-anomaly-resolution-audit-dashboard-b.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenantA->id,
            'name' => 'Admin Leave Anomaly Resolution Audit Dashboard',
            'email' => 'admin-leave-anomaly-resolution-audit-dashboard@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $manager = User::create([
            'tenant_id' => $tenantA->id,
            'name' => 'Manager Leave Anomaly Resolution Audit Dashboard',
            'email' => 'manager-leave-anomaly-resolution-audit-dashboard@example.test',
            'password' => 'password',
            'role' => 'manager',
            'status' => 'active',
        ]);

        $employeeUser = User::create([
            'tenant_id' => $tenantA->id,
            'name' => 'Employee Leave Anomaly Resolution Audit Dashboard',
            'email' => 'employee-leave-anomaly-resolution-audit-dashboard@example.test',
            'password' => 'password',
            'role' => 'employee',
            'status' => 'active',
        ]);

        $managerTenantB = User::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Manager Leave Anomaly Resolution Audit Dashboard B',
            'email' => 'manager-leave-anomaly-resolution-audit-dashboard-b@example.test',
            'password' => 'password',
            'role' => 'manager',
            'status' => 'active',
        ]);

        $employeeSpike = Employee::create([
            'tenant_id' => $tenantA->id,
            'user_id' => $employeeUser->id,
            'employee_code' => 'LRAD-001',
            'name' => 'Employee Lonjakan',
            'email' => 'employee-lonjakan-resolution-audit-dashboard@example.test',
            'status' => 'active',
        ]);

        $employeePattern = Employee::create([
            'tenant_id' => $tenantA->id,
            'employee_code' => 'LRAD-002',
            'name' => 'Employee Pola',
            'email' => 'employee-pola-resolution-audit-dashboard@example.test',
            'status' => 'active',
        ]);

        $employeeCarry = Employee::create([
            'tenant_id' => $tenantA->id,
            'employee_code' => 'LRAD-003',
            'name' => 'Employee Carry',
            'email' => 'employee-carry-resolution-audit-dashboard@example.test',
            'status' => 'active',
        ]);

        $employeeTenantB = Employee::create([
            'tenant_id' => $tenantB->id,
            'employee_code' => 'LRAD-101',
            'name' => 'Employee Tenant Dua',
            'email' => 'employee-tenant-dua-resolution-audit-dashboard@example.test',
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

        $this->makeLeave($tenantA, $employeeCarry, '2026-01-12', '2026-01-23', 'approved', 'Carry 2026');

        $this->makeLeave($tenantB, $employeeTenantB, '2025-01-06', '2025-01-06', 'approved', 'Baseline Januari B');
        $this->makeLeave($tenantB, $employeeTenantB, '2025-02-06', '2025-02-06', 'approved', 'Baseline Februari B');
        $this->makeLeave($tenantB, $employeeTenantB, '2025-03-01', '2025-03-08', 'approved', 'Lonjakan Maret B');

        $this->actingAs($manager)->get(route('leaves.anomalies'))->assertOk();

        $spikeNotification = $manager->fresh()->notifications
            ->first(fn ($item) => data_get($item->data, 'employee_name') === 'Employee Lonjakan' && data_get($item->data, 'category') === 'leave_anomaly');
        $this->actingAs($manager)->post(route('leaves.anomalies.resolve', $spikeNotification->id), [
            'resolution_note' => 'Audit dashboard untuk lonjakan April sudah diverifikasi HR.',
            'resolution_action' => 'Investigasi',
        ])->assertRedirect();

        $patternNotification = $manager->fresh()->notifications
            ->first(fn ($item) => data_get($item->data, 'employee_name') === 'Employee Pola' && data_get($item->data, 'category') === 'leave_anomaly');
        $this->actingAs($manager)->post(route('leaves.anomalies.resolve', $patternNotification->id), [
            'resolution_note' => 'Disetujui khusus untuk kebutuhan keluarga yang mendesak.',
            'resolution_action' => 'Disetujui Khusus',
        ])->assertRedirect();

        Carbon::setTestNow('2025-03-20 10:00:00');
        $this->actingAs($managerTenantB)->get(route('leaves.anomalies'))->assertOk();
        $notificationTenantB = $managerTenantB->fresh()->notifications
            ->first(fn ($item) => data_get($item->data, 'employee_name') === 'Employee Tenant Dua' && data_get($item->data, 'category') === 'leave_anomaly');
        $this->actingAs($managerTenantB)->post(route('leaves.anomalies.resolve', $notificationTenantB->id), [
            'resolution_note' => 'Teguran diberikan untuk lonjakan anomali tenant kedua.',
            'resolution_action' => 'Teguran',
        ])->assertRedirect();
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