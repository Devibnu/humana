<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendancesIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_tabel_tampil_dengan_data_attendance(): void
    {
        [$admin, $tenant, $employee, $workLocation] = $this->makeAttendanceContext();

        $attendance = Attendance::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'date' => '2026-04-20',
            'check_in' => '08:00',
            'check_out' => '17:00',
            'status' => 'present',
        ]);

        AttendanceLog::create([
            'attendance_id' => $attendance->id,
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'work_location_id' => $workLocation->id,
            'latitude' => -6.2002000,
            'longitude' => 106.8168000,
            'distance_meters' => 25.50,
        ]);

        $response = $this->actingAs($admin)->get(route('attendances.index'));

        $response->assertOk();
        $response->assertSee('Daftar Kehadiran');
        $response->assertSee('data-testid="attendances-table"', false);
        $response->assertSee('Attendance Index Employee');
        $response->assertSee('Attendance Index Office');
        $response->assertSee('08:00');
        $response->assertSee('17:00');
        $response->assertSee('-6.2002000, 106.8168000');
        $response->assertSee('data-testid="attendance-status-'.$attendance->id.'"', false);
        $response->assertSee('Hadir');
        $response->assertSee('data-testid="btn-open-add-attendance-modal"', false);
        $response->assertSee('data-testid="btn-view-attendance-'.$attendance->id.'"', false);
        $response->assertSee('data-testid="btn-edit-attendance-'.$attendance->id.'"', false);
        $response->assertSee('data-testid="btn-delete-attendance-'.$attendance->id.'"', false);
        $response->assertSee('title="Lihat"', false);
        $response->assertSee('title="Edit"', false);
        $response->assertSee('title="Hapus"', false);
    }

    public function test_filter_tanggal_bekerja(): void
    {
        [$admin, $tenant, $employee, $workLocation] = $this->makeAttendanceContext();

        $attendanceInRange = Attendance::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'date' => '2026-04-20',
            'check_in' => '08:00',
            'check_out' => '17:00',
            'status' => 'present',
        ]);

        $attendanceOutOfRange = Attendance::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'date' => '2026-04-10',
            'check_in' => '08:10',
            'check_out' => '17:10',
            'status' => 'leave',
        ]);

        foreach ([$attendanceInRange, $attendanceOutOfRange] as $attendance) {
            AttendanceLog::create([
                'attendance_id' => $attendance->id,
                'tenant_id' => $tenant->id,
                'employee_id' => $employee->id,
                'work_location_id' => $workLocation->id,
                'latitude' => -6.2002000,
                'longitude' => 106.8168000,
                'distance_meters' => 25.50,
            ]);
        }

        $response = $this->actingAs($admin)->get(route('attendances.index', [
            'start_date' => '2026-04-19',
            'end_date' => '2026-04-21',
        ]));

        $response->assertOk();
        $response->assertSee('2026-04-19');
        $response->assertSee('2026-04-21');
        $response->assertSee('20 Apr 2026');
        $response->assertDontSee('10 Apr 2026');
    }

    public function test_badge_summary_sesuai_data(): void
    {
        [$admin, $tenant, $employee, $workLocation] = $this->makeAttendanceContext();

        $statuses = [
            'present' => '2026-04-18',
            'leave' => '2026-04-19',
            'sick' => '2026-04-20',
            'absent' => '2026-04-21',
        ];

        foreach ($statuses as $status => $date) {
            $attendance = Attendance::create([
                'tenant_id' => $tenant->id,
                'employee_id' => $employee->id,
                'date' => $date,
                'check_in' => '08:00',
                'check_out' => '17:00',
                'status' => $status,
            ]);

            AttendanceLog::create([
                'attendance_id' => $attendance->id,
                'tenant_id' => $tenant->id,
                'employee_id' => $employee->id,
                'work_location_id' => $workLocation->id,
                'latitude' => -6.2002000,
                'longitude' => 106.8168000,
                'distance_meters' => 25.50,
            ]);
        }

        $response = $this->actingAs($admin)->get(route('attendances.index'));

        $response->assertOk();
        $response->assertSee('data-testid="attendances-summary-present"', false);
        $response->assertSee('data-testid="attendances-summary-leave"', false);
        $response->assertSee('data-testid="attendances-summary-sick"', false);
        $response->assertSee('data-testid="attendances-summary-absent"', false);
        $response->assertSee('Hadir: 1');
        $response->assertSee('Izin: 1');
        $response->assertSee('Sakit: 1');
        $response->assertSee('Alpha: 1');
    }

    public function test_empty_state_muncul_jika_data_kosong(): void
    {
        [$admin] = $this->makeAttendanceContext();

        $response = $this->actingAs($admin)->get(route('attendances.index'));

        $response->assertOk();
        $response->assertSee('data-testid="attendances-empty-state"', false);
        $response->assertSee('Belum ada data kehadiran untuk periode ini');
    }

    protected function makeAttendanceContext(): array
    {
        $tenant = Tenant::create([
            'name' => 'Attendance Index Tenant',
            'slug' => 'attendance-index-tenant',
            'domain' => 'attendance-index-tenant.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Attendance Index Admin',
            'email' => 'attendance-index-admin@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $workLocation = WorkLocation::create([
            'tenant_id' => $tenant->id,
            'name' => 'Attendance Index Office',
            'address' => 'Jakarta Selatan',
            'latitude' => -6.2000000,
            'longitude' => 106.8166667,
            'radius' => 250,
        ]);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'work_location_id' => $workLocation->id,
            'employee_code' => 'ATI-001',
            'name' => 'Attendance Index Employee',
            'email' => 'attendance-index-employee@example.test',
            'status' => 'active',
        ]);

        return [$admin, $tenant, $employee, $workLocation];
    }
}