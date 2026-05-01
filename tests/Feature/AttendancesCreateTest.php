<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendancesCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_page_displays_localized_attendance_form(): void
    {
        [$admin, $tenant, $employee, $workLocation] = $this->makeAttendanceContext();

        $response = $this->actingAs($admin)->get(route('attendances.create'));

        $response->assertOk();
        $response->assertSee('Tambah Kehadiran');
        $response->assertSee('Assigned Work Location');
        $response->assertSee('Gunakan Lokasi Perangkat');
        $response->assertSee('Simpan Kehadiran');
        $response->assertSee('Batal');
        $response->assertSee('Karyawan');
        $response->assertSee($employee->name);
        $response->assertSee($workLocation->name);
        $response->assertSee('Hadir');
        $response->assertSee('Izin');
        $response->assertSee('Sakit');
        $response->assertSee('Alpha');
    }

    public function test_create_page_shows_empty_state_when_no_employees_exist(): void
    {
        [$admin] = $this->makeAttendanceContext(createEmployee: false);

        $response = $this->actingAs($admin)->get(route('attendances.create'));

        $response->assertOk();
        $response->assertSee('Belum ada karyawan, silakan buat karyawan terlebih dahulu');
        $response->assertDontSee('Simpan Kehadiran');
    }

    public function test_store_requires_work_location_and_saves_attendance_log(): void
    {
        [$admin, $tenant, $employee, $workLocation] = $this->makeAttendanceContext();

        $invalidResponse = $this->actingAs($admin)
            ->from(route('attendances.create'))
            ->post(route('attendances.store'), [
                'tenant_id' => $tenant->id,
                'employee_id' => $employee->id,
                'date' => '2026-04-21',
                'check_in' => '08:00',
                'check_out' => '17:00',
                'status' => 'present',
                'latitude' => -6.2002000,
                'longitude' => 106.8168000,
            ]);

        $invalidResponse->assertRedirect(route('attendances.create'));
        $invalidResponse->assertSessionHasErrors('work_location_id');

        $response = $this->actingAs($admin)->post(route('attendances.store'), [
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'work_location_id' => $workLocation->id,
            'date' => '2026-04-21',
            'check_in' => '08:00',
            'check_out' => '17:00',
            'status' => 'present',
            'latitude' => -6.2002000,
            'longitude' => 106.8168000,
        ]);

        $response->assertRedirect(route('attendances.index'));
        $response->assertSessionHas('success', 'Kehadiran berhasil ditambahkan');

        $attendance = Attendance::query()
            ->where('tenant_id', $tenant->id)
            ->where('employee_id', $employee->id)
            ->whereDate('date', '2026-04-21')
            ->first();

        $this->assertNotNull($attendance);

        $this->assertDatabaseHas('attendance_logs', [
            'attendance_id' => $attendance->id,
            'employee_id' => $employee->id,
            'work_location_id' => $workLocation->id,
            'latitude' => -6.2002000,
            'longitude' => 106.8168000,
        ]);
    }

    protected function makeAttendanceContext(bool $createEmployee = true): array
    {
        $tenant = Tenant::create([
            'name' => 'Attendance Create Tenant',
            'slug' => 'attendance-create-tenant',
            'domain' => 'attendance-create-tenant.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Attendance Create Admin',
            'email' => 'attendance-create-admin@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $workLocation = WorkLocation::create([
            'tenant_id' => $tenant->id,
            'name' => 'Attendance Create Office',
            'address' => 'Jakarta Pusat',
            'latitude' => -6.2000000,
            'longitude' => 106.8166667,
            'radius' => 250,
        ]);

        $employee = null;

        if ($createEmployee) {
            $employee = Employee::create([
                'tenant_id' => $tenant->id,
                'work_location_id' => $workLocation->id,
                'employee_code' => 'ATC-001',
                'name' => 'Attendance Create Employee',
                'email' => 'attendance-create-employee@example.test',
                'status' => 'active',
            ]);
        }

        return [$admin, $tenant, $employee, $workLocation];
    }
}