<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AttendanceValidationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected User $admin;

    protected User $employeeUser;

    protected Employee $employee;

    protected WorkLocation $workLocation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Attendance Validation Tenant',
            'slug' => 'attendance-validation-tenant',
            'domain' => 'attendance-validation-tenant.test',
            'status' => 'active',
        ]);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Attendance Validation Admin',
            'email' => 'attendance-validation-admin@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $this->employeeUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Attendance Validation Employee User',
            'email' => 'attendance-validation-employee-user@example.test',
            'password' => 'password',
            'role' => 'employee',
            'status' => 'active',
        ]);

        $this->workLocation = WorkLocation::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Attendance Validation Office',
            'address' => 'Jakarta Office',
            'latitude' => -6.2000000,
            'longitude' => 106.8166667,
            'radius' => 200,
        ]);

        $this->employee = Employee::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->employeeUser->id,
            'work_location_id' => $this->workLocation->id,
            'employee_code' => 'ATV-001',
            'name' => 'Attendance Validation Employee',
            'email' => 'attendance-validation-employee@example.test',
            'status' => 'active',
        ]);
    }

    public function test_attendance_is_saved_when_coordinates_are_within_radius(): void
    {
        $response = $this->actingAs($this->admin)->post(route('attendances.store'), [
            'tenant_id' => $this->tenant->id,
            'employee_id' => $this->employee->id,
            'work_location_id' => $this->workLocation->id,
            'date' => '2026-04-18',
            'check_in' => '08:00',
            'check_out' => '17:00',
            'status' => 'present',
            'latitude' => -6.2003000,
            'longitude' => 106.8167000,
        ]);

        $response->assertRedirect(route('attendances.index'));

        $attendance = Attendance::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('employee_id', $this->employee->id)
            ->whereDate('date', '2026-04-18')
            ->first();

        $this->assertNotNull($attendance);
        $this->assertSame('2026-04-18', $attendance->date->format('Y-m-d'));

        $this->assertDatabaseHas('attendance_logs', [
            'employee_id' => $this->employee->id,
            'work_location_id' => $this->workLocation->id,
            'latitude' => -6.2003000,
            'longitude' => 106.8167000,
        ]);
    }

    public function test_attendance_is_rejected_when_coordinates_are_outside_radius(): void
    {
        $response = $this->actingAs($this->admin)
            ->from(route('attendances.create'))
            ->post(route('attendances.store'), [
                'tenant_id' => $this->tenant->id,
                'employee_id' => $this->employee->id,
                'work_location_id' => $this->workLocation->id,
                'date' => '2026-04-19',
                'check_in' => '08:00',
                'check_out' => '17:00',
                'status' => 'present',
                'latitude' => -6.2100000,
                'longitude' => 106.8300000,
            ]);

        $response->assertRedirect(route('attendances.create'));
        $response->assertSessionHasErrors([
            'latitude' => 'Kehadiran berada di luar radius lokasi kerja yang diizinkan.',
        ]);

        $this->assertDatabaseMissing('attendances', [
            'employee_id' => $this->employee->id,
            'date' => '2026-04-19',
        ]);
    }

    public function test_users_table_still_exists_and_acting_as_user_still_works(): void
    {
        $this->assertTrue(Schema::hasTable('users'));

        $response = $this->actingAs($this->employeeUser)->get(route('attendances.index'));

        $response->assertOk();
        $this->assertAuthenticatedAs($this->employeeUser);
    }
}