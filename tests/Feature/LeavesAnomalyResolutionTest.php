<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeavesAnomalyResolutionTest extends TestCase
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

    public function test_manager_bisa_menambahkan_catatan_resolusi(): void
    {
        [$admin, $manager, $employeeUser] = $this->makeContext();

        $this->actingAs($manager)->get(route('leaves.anomalies'))->assertOk();

        $notification = $manager->fresh()->notifications
            ->first(fn ($item) => data_get($item->data, 'employee_name') === 'Employee Lonjakan' && data_get($item->data, 'category') === 'leave_anomaly');

        $this->assertNotNull($notification);

        $response = $this->actingAs($manager)->post(route('leaves.anomalies.resolve', $notification->id), [
            'resolution_note' => 'Sudah diinvestigasi, lonjakan terjadi karena kebutuhan keluarga mendesak dan disetujui khusus.',
            'resolution_action' => 'Disetujui Khusus',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('leaves_anomaly_resolutions', [
            'anomaly_id' => data_get($notification->data, 'fingerprint'),
            'manager_id' => $manager->id,
            'resolution_action' => 'Disetujui Khusus',
            'resolution_note' => 'Sudah diinvestigasi, lonjakan terjadi karena kebutuhan keluarga mendesak dan disetujui khusus.',
        ]);

        $managerNotification = $manager->fresh()->notifications
            ->first(fn ($item) => data_get($item->data, 'fingerprint') === data_get($notification->data, 'fingerprint'));
        $adminNotification = $admin->fresh()->notifications
            ->first(fn ($item) => data_get($item->data, 'fingerprint') === data_get($notification->data, 'fingerprint'));

        $this->assertSame('resolved', data_get($managerNotification->data, 'resolution_status'));
        $this->assertSame('Resolved', data_get($managerNotification->data, 'resolution_status_label'));
        $this->assertSame('Disetujui Khusus', data_get($managerNotification->data, 'resolution_action'));
        $this->assertSame('Sudah diinvestigasi, lonjakan terjadi karena kebutuhan keluarga mendesak dan disetujui khusus.', data_get($managerNotification->data, 'resolution_note'));
        $this->assertSame('resolved', data_get($adminNotification->data, 'resolution_status'));

        $dashboardResponse = $this->actingAs($manager)->get(route('leaves.anomalies'));

        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee('Resolved');
        $dashboardResponse->assertSee('Disetujui Khusus');
        $dashboardResponse->assertSee('Sudah diinvestigasi, lonjakan terjadi karena kebutuhan keluarga mendesak dan disetujui khusus.');

        $this->assertCount(0, $employeeUser->fresh()->notifications);
    }

    public function test_employee_tidak_bisa_resolve_anomali(): void
    {
        [, $manager, $employeeUser] = $this->makeContext();

        $this->actingAs($manager)->get(route('leaves.anomalies'))->assertOk();
        $notification = $manager->fresh()->notifications->first();

        $this->actingAs($employeeUser)
            ->post(route('leaves.anomalies.resolve', $notification->id), [
                'resolution_note' => 'Mencoba resolve tanpa izin.',
                'resolution_action' => 'Investigasi',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('leaves_anomaly_resolutions', 0);
    }

    protected function makeContext(): array
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Leave Anomaly Resolution',
            'slug' => 'tenant-leave-anomaly-resolution',
            'domain' => 'tenant-leave-anomaly-resolution.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Leave Anomaly Resolution',
            'email' => 'admin-leave-anomaly-resolution@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $manager = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Manager Leave Anomaly Resolution',
            'email' => 'manager-leave-anomaly-resolution@example.test',
            'password' => 'password',
            'role' => 'manager',
            'status' => 'active',
        ]);

        $employeeUser = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Employee Leave Anomaly Resolution',
            'email' => 'employee-leave-anomaly-resolution@example.test',
            'password' => 'password',
            'role' => 'employee',
            'status' => 'active',
        ]);

        $employeeSpike = Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $employeeUser->id,
            'employee_code' => 'LAR-001',
            'name' => 'Employee Lonjakan',
            'email' => 'employee-lonjakan-resolution@example.test',
            'status' => 'active',
        ]);

        $employeePattern = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'LAR-002',
            'name' => 'Employee Pola',
            'email' => 'employee-pola-resolution@example.test',
            'status' => 'active',
        ]);

        $employeeCarry = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'LAR-003',
            'name' => 'Employee Carry',
            'email' => 'employee-carry-resolution@example.test',
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