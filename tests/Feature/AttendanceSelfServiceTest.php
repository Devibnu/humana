<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkLocation;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
        Storage::fake('public');
        Carbon::setTestNow('2026-04-30 08:00:00');
        [$user, $employee, $workLocation] = $this->makeEmployeeContext();

        $checkInResponse = $this->actingAs($user)
            ->from(route('attendances.index'))
            ->post(route('attendances.self-service'), [
                'latitude' => -6.0336840,
                'longitude' => 106.1525630,
                'attendance_photo' => UploadedFile::fake()->image('check-in.jpg'),
            ]);

        $checkInResponse->assertRedirect(route('attendances.index'));
        $checkInResponse->assertSessionHas('success', 'Absen masuk berhasil disimpan.');

        $attendance = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('date', '2026-04-30')
            ->first();

        $this->assertNotNull($attendance);
        $this->assertSame('08:00', substr($attendance->check_in, 0, 5));
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
                'attendance_photo' => UploadedFile::fake()->image('check-out.jpg'),
            ]);

        $checkOutResponse->assertRedirect(route('attendances.index'));
        $checkOutResponse->assertSessionHas('success', 'Absen pulang berhasil disimpan.');

        $this->assertSame('17:05', substr($attendance->fresh()->check_out, 0, 5));
    }

    public function test_employee_self_attendance_calculates_late_and_early_leave_from_work_schedule(): void
    {
        Storage::fake('public');
        Carbon::setTestNow('2026-04-30 08:15:00');
        [$user, $employee] = $this->makeEmployeeContext();

        $this->actingAs($user)
            ->post(route('attendances.self-service'), [
                'latitude' => -6.0336840,
                'longitude' => 106.1525630,
                'attendance_photo' => UploadedFile::fake()->image('late-check-in.jpg'),
            ])
            ->assertRedirect(route('attendances.index'));

        $attendance = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('date', '2026-04-30')
            ->firstOrFail();

        $this->assertSame('late', $attendance->status);
        $this->assertSame(15, $attendance->late_minutes);
        $this->assertSame('08:00', substr($attendance->scheduled_check_in, 0, 5));
        $this->assertSame('17:00', substr($attendance->scheduled_check_out, 0, 5));

        Carbon::setTestNow('2026-04-30 16:30:00');

        $this->actingAs($user)
            ->post(route('attendances.self-service'), [
                'latitude' => -6.0336840,
                'longitude' => 106.1525630,
                'attendance_photo' => UploadedFile::fake()->image('early-check-out.jpg'),
            ])
            ->assertRedirect(route('attendances.index'));

        $this->assertSame(30, $attendance->fresh()->early_leave_minutes);
    }

    public function test_employee_self_attendance_can_check_out_open_night_shift_on_next_day(): void
    {
        Storage::fake('public');
        Carbon::setTestNow('2026-04-30 23:05:00');
        [$user, $employee] = $this->makeEmployeeContext(scheduleCode: 'shift_malam');

        $this->actingAs($user)
            ->post(route('attendances.self-service'), [
                'latitude' => -6.0336840,
                'longitude' => 106.1525630,
                'attendance_photo' => UploadedFile::fake()->image('night-check-in.jpg'),
            ])
            ->assertRedirect(route('attendances.index'));

        Carbon::setTestNow('2026-05-01 07:05:00');

        $this->actingAs($user)
            ->post(route('attendances.self-service'), [
                'latitude' => -6.0336840,
                'longitude' => 106.1525630,
                'attendance_photo' => UploadedFile::fake()->image('night-check-out.jpg'),
            ])
            ->assertRedirect(route('attendances.index'));

        $attendance = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('date', '2026-04-30')
            ->firstOrFail();

        $this->assertSame('23:05', substr($attendance->check_in, 0, 5));
        $this->assertSame('07:05', substr($attendance->check_out, 0, 5));
        $this->assertSame('23:00', substr($attendance->scheduled_check_in, 0, 5));
        $this->assertSame('07:00', substr($attendance->scheduled_check_out, 0, 5));
    }

    public function test_employee_self_attendance_rejects_location_outside_radius(): void
    {
        Storage::fake('public');
        Carbon::setTestNow('2026-04-30 08:15:00');
        [$user, $employee] = $this->makeEmployeeContext();

        $response = $this->actingAs($user)
            ->from(route('attendances.index'))
            ->post(route('attendances.self-service'), [
                'latitude' => -6.0400000,
                'longitude' => 106.1600000,
                'attendance_photo' => UploadedFile::fake()->image('outside.jpg'),
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

    protected function makeEmployeeContext(bool $withWorkLocation = true, string $scheduleCode = 'office_hour'): array
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

        $workSchedule = WorkSchedule::query()
            ->where('tenant_id', $tenant->id)
            ->where('code', $scheduleCode)
            ->first();

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'work_location_id' => $workLocation?->id,
            'work_schedule_id' => $workSchedule?->id,
            'employee_code' => 'SAT-001',
            'name' => 'Self Attendance Employee',
            'email' => 'self-attendance-employee@example.test',
            'status' => 'active',
        ]);

        $user->update(['employee_id' => $employee->id]);

        return [$user->fresh(), $employee, $workLocation];
    }
}
