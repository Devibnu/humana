<?php

namespace Tests\Feature;

use App\Exports\LeavesAnomalyResolutionAuditLogExport;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Tenant;
use App\Models\User;
use App\Services\LeavesAnomalyService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class LeavesAnomalyResolutionAuditLogTest extends TestCase
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

    public function test_route_leaves_anomalies_resolutions_log_accessible_by_admin(): void
    {
        [$admin, $manager, $employeeUser] = $this->makeContext();

        $this->actingAs($admin)->get(route('leaves.anomalies.resolutions.log'))->assertOk()->assertViewIs('leaves.anomalies.resolutions.log');
        $this->actingAs($manager)->get(route('leaves.anomalies.resolutions.log'))->assertOk();
        $this->actingAs($employeeUser)->get(route('leaves.anomalies.resolutions.log'))->assertForbidden();
    }

    public function test_tabel_log_menampilkan_data_resolusi_sesuai_db(): void
    {
        [$admin] = $this->makeContext();

        $response = $this->actingAs($admin)->get(route('leaves.anomalies.resolutions.log'));

        $response->assertOk();
        $response->assertSee('data-testid="leave-anomaly-resolution-audit-table-container"', false);
        $response->assertSee('Employee Lonjakan');
        $response->assertSee('Lonjakan');
        $response->assertSee('Manager Leave Anomaly Resolution Audit');
        $response->assertSee('Investigasi');
        $response->assertSee('Audit trail untuk lonjakan April sudah diverifikasi HR.');
        $response->assertSee('22 Apr 2026 10:00');
    }

    public function test_filter_tenant_bulan_tahun_bekerja(): void
    {
        [$admin, , , $tenantA, $tenantB] = $this->makeContext();

        $response = $this->actingAs($admin)->get(route('leaves.anomalies.resolutions.log', [
            'tenant_id' => $tenantB->id,
            'month' => 3,
            'year' => 2025,
        ]));

        $response->assertOk();
        $response->assertSee('Employee Tenant Dua');
        $response->assertDontSee('Employee Lonjakan');
        $response->assertSee('data-testid="leave-anomaly-resolution-audit-tenant-filter"', false);
        $response->assertSee('data-testid="leave-anomaly-resolution-audit-month-filter"', false);
        $response->assertSee('data-testid="leave-anomaly-resolution-audit-year-filter"', false);
    }

    public function test_export_tombol_menghasilkan_file_dengan_data_log(): void
    {
        Excel::fake();
        [$admin, , $employeeUser, $tenantA] = $this->makeContext();

        $response = $this->actingAs($admin)->get(route('leaves.anomalies.resolutions.log'));

        $response->assertSee('data-testid="leave-anomaly-resolution-audit-export-pdf"', false);
        $response->assertSee('data-testid="leave-anomaly-resolution-audit-export-xlsx"', false);

        $this->actingAs($employeeUser)
            ->get(route('leaves.anomalies.resolutions.log.export.xlsx', ['tenant_id' => $tenantA->id]))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('leaves.anomalies.resolutions.log.export.xlsx', ['tenant_id' => $tenantA->id]))
            ->assertOk();

        Excel::assertDownloaded('leaves_anomaly_resolution_audit_log_tenant-leave-anomaly-resolution-audit-a_20260422.xlsx', function (LeavesAnomalyResolutionAuditLogExport $export) {
            $rows = array_map('array_values', $export->collection()->toArray());

            $this->assertContains([
                'Employee Lonjakan',
                'Lonjakan',
                'Employee Lonjakan mengalami lonjakan cuti bulan April (7 hari vs rata-rata 2.0 hari).',
                'April 2026',
                'Manager Leave Anomaly Resolution Audit',
                'Investigasi',
                'Audit trail untuk lonjakan April sudah diverifikasi HR.',
                '22 Apr 2026 10:00',
            ], $rows);

            return true;
        });

        $pdfResponse = $this->actingAs($admin)
            ->get(route('leaves.anomalies.resolutions.log.export.pdf', ['tenant_id' => $tenantA->id]));

        $pdfResponse->assertOk();
        $pdfResponse->assertHeader('Content-Type', 'application/pdf');
        $pdfResponse->assertHeader('Content-Disposition', 'attachment; filename="leaves_anomaly_resolution_audit_log_tenant-leave-anomaly-resolution-audit-a_20260422.pdf"');

        $payload = app(LeavesAnomalyService::class)->buildResolutionAuditLogExportPayload($admin, $tenantA->id, 4, 2026, null);
        $rendered = view('leaves.exports.anomaly-resolution-audit-log-pdf', $payload)->render();

        $this->assertStringContainsString('Audit Log Resolusi Anomali Cuti', $rendered);
        $this->assertStringContainsString('Employee Lonjakan', $rendered);
        $this->assertStringContainsString('Audit trail untuk lonjakan April sudah diverifikasi HR.', $rendered);
    }

    protected function makeContext(): array
    {
        $tenantA = Tenant::create([
            'name' => 'Tenant Leave Anomaly Resolution Audit A',
            'slug' => 'tenant-leave-anomaly-resolution-audit-a',
            'domain' => 'tenant-leave-anomaly-resolution-audit-a.test',
            'status' => 'active',
        ]);

        $tenantB = Tenant::create([
            'name' => 'Tenant Leave Anomaly Resolution Audit B',
            'slug' => 'tenant-leave-anomaly-resolution-audit-b',
            'domain' => 'tenant-leave-anomaly-resolution-audit-b.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenantA->id,
            'name' => 'Admin Leave Anomaly Resolution Audit',
            'email' => 'admin-leave-anomaly-resolution-audit@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $manager = User::create([
            'tenant_id' => $tenantA->id,
            'name' => 'Manager Leave Anomaly Resolution Audit',
            'email' => 'manager-leave-anomaly-resolution-audit@example.test',
            'password' => 'password',
            'role' => 'manager',
            'status' => 'active',
        ]);

        $employeeUser = User::create([
            'tenant_id' => $tenantA->id,
            'name' => 'Employee Leave Anomaly Resolution Audit',
            'email' => 'employee-leave-anomaly-resolution-audit@example.test',
            'password' => 'password',
            'role' => 'employee',
            'status' => 'active',
        ]);

        $managerTenantB = User::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Manager Leave Anomaly Resolution Audit B',
            'email' => 'manager-leave-anomaly-resolution-audit-b@example.test',
            'password' => 'password',
            'role' => 'manager',
            'status' => 'active',
        ]);

        $employeeSpike = Employee::create([
            'tenant_id' => $tenantA->id,
            'user_id' => $employeeUser->id,
            'employee_code' => 'LRA-001',
            'name' => 'Employee Lonjakan',
            'email' => 'employee-lonjakan-resolution-audit@example.test',
            'status' => 'active',
        ]);

        $employeeTenantB = Employee::create([
            'tenant_id' => $tenantB->id,
            'employee_code' => 'LRA-101',
            'name' => 'Employee Tenant Dua',
            'email' => 'employee-tenant-dua-resolution-audit@example.test',
            'status' => 'active',
        ]);

        $this->makeLeave($tenantA, $employeeSpike, '2026-01-06', '2026-01-07', 'approved', 'Baseline Januari');
        $this->makeLeave($tenantA, $employeeSpike, '2026-02-10', '2026-02-11', 'approved', 'Baseline Februari');
        $this->makeLeave($tenantA, $employeeSpike, '2026-03-03', '2026-03-04', 'approved', 'Baseline Maret');
        $this->makeLeave($tenantA, $employeeSpike, '2026-04-01', '2026-04-07', 'approved', 'Lonjakan April');

        $this->makeLeave($tenantB, $employeeTenantB, '2025-01-06', '2025-01-06', 'approved', 'Baseline Januari B');
        $this->makeLeave($tenantB, $employeeTenantB, '2025-02-06', '2025-02-06', 'approved', 'Baseline Februari B');
        $this->makeLeave($tenantB, $employeeTenantB, '2025-03-01', '2025-03-08', 'approved', 'Lonjakan Maret B');

        $this->actingAs($manager)->get(route('leaves.anomalies'))->assertOk();

        $notification = $manager->fresh()->notifications
            ->first(fn ($item) => data_get($item->data, 'employee_name') === 'Employee Lonjakan' && data_get($item->data, 'category') === 'leave_anomaly');

        $this->actingAs($manager)->post(route('leaves.anomalies.resolve', $notification->id), [
            'resolution_note' => 'Audit trail untuk lonjakan April sudah diverifikasi HR.',
            'resolution_action' => 'Investigasi',
        ])->assertRedirect();

        Carbon::setTestNow('2025-03-20 10:00:00');
        $this->actingAs($managerTenantB)->get(route('leaves.anomalies'))->assertOk();
        $notificationTenantB = $managerTenantB->fresh()->notifications
            ->first(fn ($item) => data_get($item->data, 'employee_name') === 'Employee Tenant Dua' && data_get($item->data, 'category') === 'leave_anomaly');
        $this->actingAs($managerTenantB)->post(route('leaves.anomalies.resolve', $notificationTenantB->id), [
            'resolution_note' => 'Teguran diberikan untuk lonjakan anomali di tenant kedua.',
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