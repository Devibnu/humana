<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\LeavesAnomalyNotification;
use App\Services\LeavesAnomalyService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LeavesAnomalyNotificationTest extends TestCase
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

    public function test_notifikasi_terkirim_ke_manager_dan_admin_saat_anomali_terdeteksi(): void
    {
        [$admin, $manager, $employeeUser, $tenant] = $this->makeContext();

        Notification::fake();

        app(LeavesAnomalyService::class)->buildDashboardPayload($admin, $tenant->id);

        Notification::assertSentTo($manager, LeavesAnomalyNotification::class, function (LeavesAnomalyNotification $notification, array $channels) use ($manager) {
            $payload = $notification->toArray($manager);

            if (($payload['anomaly_type'] ?? null) !== 'lonjakan') {
                return false;
            }

            $this->assertSame(['mail', 'database', 'broadcast'], $notification->via($manager));
            $this->assertContains('mail', $channels);
            $this->assertContains('database', $channels);
            $this->assertContains('broadcast', $channels);
            $this->assertSame('Employee Lonjakan', $payload['employee_name']);
            $this->assertSame('Lonjakan', $payload['anomaly_type_label']);
            $this->assertSame('Employee Lonjakan mengalami lonjakan cuti bulan April (7 hari vs rata-rata 2.0 hari).', $payload['description']);
            $this->assertSame('leave_anomaly', $payload['category']);
            $this->assertNotEmpty($payload['detected_at']);

            return true;
        });

        Notification::assertSentTo($admin, LeavesAnomalyNotification::class);
        Notification::assertNotSentTo($employeeUser, LeavesAnomalyNotification::class);
    }

    public function test_in_app_alert_muncul_di_dashboard_dan_bisa_tandai_baca(): void
    {
        [$admin, $manager, $employeeUser, $tenant] = $this->makeContext();

        $response = $this->actingAs($manager)->get(route('leaves.anomalies'));

        $response->assertOk();
        $response->assertSee('Notifikasi Anomali');
        $response->assertSee('Tandai Dibaca');
        $response->assertSee('Employee Lonjakan → Lonjakan cuti bulan April');
        $response->assertSee('Employee Lonjakan mengalami lonjakan cuti bulan April (7 hari vs rata-rata 2.0 hari).');

        $this->assertCount(3, $manager->fresh()->notifications);
        $this->assertCount(3, $admin->fresh()->notifications);
        $this->assertCount(0, $employeeUser->fresh()->notifications);

        $notification = $manager->fresh()->notifications()->latest()->first();

        $this->actingAs($manager)
            ->patch(route('leaves.anomalies.notifications.read', $notification))
            ->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);

        $this->actingAs($manager)
            ->patch(route('leaves.anomalies.notifications.unread', $notification->fresh()))
            ->assertRedirect();

        $this->assertNull($notification->fresh()->read_at);
    }

    protected function makeContext(): array
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Leave Anomaly Notification',
            'slug' => 'tenant-leave-anomaly-notification',
            'domain' => 'tenant-leave-anomaly-notification.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Leave Anomaly Notification',
            'email' => 'admin-leave-anomaly-notification@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $manager = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Manager Leave Anomaly Notification',
            'email' => 'manager-leave-anomaly-notification@example.test',
            'password' => 'password',
            'role' => 'manager',
            'status' => 'active',
        ]);

        $employeeUser = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Employee Leave Anomaly Notification',
            'email' => 'employee-leave-anomaly-notification@example.test',
            'password' => 'password',
            'role' => 'employee',
            'status' => 'active',
        ]);

        $employeeSpike = Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $employeeUser->id,
            'employee_code' => 'LAN-101',
            'name' => 'Employee Lonjakan',
            'email' => 'employee-lonjakan-notification@example.test',
            'status' => 'active',
        ]);

        $employeePattern = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'LAN-102',
            'name' => 'Employee Pola',
            'email' => 'employee-pola-notification@example.test',
            'status' => 'active',
        ]);

        $employeeCarry = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'LAN-103',
            'name' => 'Employee Carry',
            'email' => 'employee-carry-notification@example.test',
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