<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkLocation;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceSelfServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_employee_with_work_location_sees_self_attendance_button(): void
    {
        [$user, $employee, $workLocation] = $this->makeEmployeeContext();

        $response = $this->actingAs($user)->get(route('attendances.index'));

        $response->assertOk();
        $response->assertSee('data-testid="btn-self-attendance"', false);
        $response->assertSee('Absen Masuk');
        $response->assertSee('Lokasi: '.$workLocation->name);
        $response->assertSee('Radius '.$workLocation->radius.' meter');
        $response->assertDontSee('data-testid="btn-open-add-attendance-modal"', false);
    }

    public function test_employee_can_check_in_and_check_out_within_work_location_radius(): void
    {
        Carbon::setTestNow('2026-04-30 08:15:00');
        [$user, $employee, $workLocation] = $this->makeEmployeeContext();

        $checkInResponse = $this->actingAs($user)
            ->from(route('attendances.index'))
            ->post(route('attendances.self-service'), [
                'latitude' => -6.0336840,
                'longitude' => 106.1525630,
            ]);

        $checkInResponse->assertRedirect(route('attendances.index'));
        $checkInResponse->assertSessionHas('success', 'Absen masuk berhasil disimpan.');

        $attendance = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('date', '2026-04-30')
            ->first();

        $this->assertNotNull($attendance);
        $this->assertSame('08:15', substr($attendance->check_in, 0, 5));
        $this->assertNull($attendance->check_out);
        $this->assertSame('present', $attendance->status);

        $this->assertDatabaseHas('attendance_logs', [
            'attendance_id' => $attendance->id,
            'employee_id' => $employee->id,
            'work_location_id' => $workLocation->id,
            'latitude' => -6.0336840,
            'longitude' => 106.1525630,
        ]);

        Carbon::setTestNow('2026-04-30 17:05:00');

        $checkOutResponse = $this->actingAs($user)
            ->from(route('attendances.index'))
            ->post(route('attendances.self-service'), [
                'latitude' => -6.0336840,
                'longitude' => 106.1525630,
            ]);

        $checkOutResponse->assertRedirect(route('attendances.index'));
        $checkOutResponse->assertSessionHas('success', 'Absen pulang berhasil disimpan.');

        $this->assertSame('17:05', substr($attendance->fresh()->check_out, 0, 5));
    }

    public function test_employee_self_attendance_rejects_location_outside_radius(): void
    {
        Carbon::setTestNow('2026-04-30 08:15:00');
        [$user, $employee] = $this->makeEmployeeContext();

        $response = $this->actingAs($user)
            ->from(route('attendances.index'))
            ->post(route('attendances.self-service'), [
                'latitude' => -6.0400000,
                'longitude' => 106.1600000,
            ]);

        $response->assertRedirect(route('attendances.index'));
        $response->assertSessionHasErrors([
            'latitude' => 'Kehadiran berada di luar radius lokasi kerja yang diizinkan.',
        ]);

        $this->assertDatabaseMissing('attendances', [
            'employee_id' => $employee->id,
            'date' => '2026-04-30',
        ]);
    }

    public function test_employee_without_work_location_sees_disabled_self_attendance_button(): void
    {
        [$user] = $this->makeEmployeeContext(withWorkLocation: false);

        $response = $this->actingAs($user)->get(route('attendances.index'));

        $response->assertOk();
        $response->assertSee('data-testid="btn-self-attendance-disabled"', false);
        $response->assertSee('Lokasi kerja belum diatur');
    }

    protected function makeEmployeeContext(bool $withWorkLocation = true): array
    {
        $tenant = Tenant::create([
            'name' => 'Self Attendance Tenant',
            'slug' => 'self-attendance-tenant',
            'domain' => 'self-attendance-tenant.test',
            'status' => 'active',
        ]);

        $role = Role::firstOrCreate(
            ['name' => 'Employee'],
            ['description' => 'Akses terbatas']
        );

        RolePermission::updateOrCreate([
            'role_id' => $role->id,
            'menu_key' => 'attendances',
        ], [
            'can_access' => true,
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Self Attendance Employee User',
            'email' => 'self-attendance-employee@example.test',
            'password' => 'password',
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $workLocation = $withWorkLocation
            ? WorkLocation::create([
                'tenant_id' => $tenant->id,
                'name' => 'Self Attendance Office',
                'address' => 'Serang',
                'latitude' => -6.0336840,
                'longitude' => 106.1525630,
                'radius' => 50,
            ])
            : null;

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'work_location_id' => $workLocation?->id,
            'employee_code' => 'SAT-001',
            'name' => 'Self Attendance Employee',
            'email' => 'self-attendance-employee@example.test',
            'status' => 'active',
        ]);

        $user->update(['employee_id' => $employee->id]);

        return [$user->fresh(), $employee, $workLocation];
    }
}
