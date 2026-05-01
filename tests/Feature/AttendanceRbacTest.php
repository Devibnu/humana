<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceRbacTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;

    protected Tenant $tenantB;

    protected Employee $employeeA;

    protected Employee $employeeB;

    protected WorkLocation $workLocationA;

    protected WorkLocation $workLocationB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::create([
            'name' => 'Attendance Tenant A',
            'slug' => 'attendance-tenant-a',
            'domain' => 'attendance-tenant-a.test',
            'status' => 'active',
        ]);

        $this->tenantB = Tenant::create([
            'name' => 'Attendance Tenant B',
            'slug' => 'attendance-tenant-b',
            'domain' => 'attendance-tenant-b.test',
            'status' => 'active',
        ]);

        Position::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Attendance Supervisor A',
            'status' => 'active',
        ]);

        Position::create([
            'tenant_id' => $this->tenantB->id,
            'name' => 'Attendance Supervisor B',
            'status' => 'active',
        ]);

        Department::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Attendance Ops A',
            'status' => 'active',
        ]);

        Department::create([
            'tenant_id' => $this->tenantB->id,
            'name' => 'Attendance Ops B',
            'status' => 'active',
        ]);

        $this->workLocationA = WorkLocation::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Attendance Office A',
            'address' => 'Jakarta Selatan',
            'latitude' => -6.2000000,
            'longitude' => 106.8166667,
            'radius' => 250,
        ]);

        $this->workLocationB = WorkLocation::create([
            'tenant_id' => $this->tenantB->id,
            'name' => 'Attendance Office B',
            'address' => 'Bandung',
            'latitude' => -6.9147440,
            'longitude' => 107.6098100,
            'radius' => 250,
        ]);

        $this->employeeA = Employee::create([
            'tenant_id' => $this->tenantA->id,
            'work_location_id' => $this->workLocationA->id,
            'employee_code' => 'ATT-A-1',
            'name' => 'Attendance Employee A',
            'email' => 'attendance-employee-a@example.test',
            'status' => 'active',
        ]);

        $this->employeeB = Employee::create([
            'tenant_id' => $this->tenantB->id,
            'work_location_id' => $this->workLocationB->id,
            'employee_code' => 'ATT-B-1',
            'name' => 'Attendance Employee B',
            'email' => 'attendance-employee-b@example.test',
            'status' => 'active',
        ]);
    }

    public function test_admin_hr_has_full_attendance_crud(): void
    {
        $admin = $this->makeUser('admin_hr', $this->tenantA, 'attendance-admin@example.test');

        $this->actingAs($admin)->get(route('attendances.index'))->assertOk();
        $this->actingAs($admin)->get(route('attendances.create'))->assertOk();

        $this->actingAs($admin)->post(route('attendances.store'), [
            'tenant_id' => $this->tenantA->id,
            'employee_id' => $this->employeeA->id,
            'work_location_id' => $this->workLocationA->id,
            'date' => '2026-04-17',
            'check_in' => '08:00',
            'check_out' => '17:00',
            'status' => 'present',
            'latitude' => -6.2002000,
            'longitude' => 106.8168000,
        ])->assertRedirect(route('attendances.index'));

        $attendance = Attendance::where('employee_id', $this->employeeA->id)
            ->whereDate('date', '2026-04-17')
            ->firstOrFail();

        $this->actingAs($admin)->get(route('attendances.edit', $attendance))->assertOk();

        $this->actingAs($admin)->put(route('attendances.update', $attendance), [
            'tenant_id' => $this->tenantB->id,
            'employee_id' => $this->employeeB->id,
            'work_location_id' => $this->workLocationB->id,
            'date' => '2026-04-18',
            'check_in' => '09:00',
            'check_out' => '18:00',
            'status' => 'late',
            'latitude' => -6.9149000,
            'longitude' => 107.6099000,
        ])->assertRedirect(route('attendances.index'));

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'tenant_id' => $this->tenantB->id,
            'employee_id' => $this->employeeB->id,
            'status' => 'late',
        ]);
        $this->assertSame('2026-04-18', $attendance->fresh()->date->format('Y-m-d'));

        $this->actingAs($admin)->delete(route('attendances.destroy', $attendance))->assertRedirect(route('attendances.index'));
        $this->assertDatabaseMissing('attendances', ['id' => $attendance->id]);
    }

    public function test_manager_can_view_create_and_update_attendance_only_for_own_tenant(): void
    {
        $manager = $this->makeUser('manager', $this->tenantA, 'attendance-manager@example.test');

        $attendanceA = Attendance::create([
            'tenant_id' => $this->tenantA->id,
            'employee_id' => $this->employeeA->id,
            'date' => '2026-04-10',
            'check_in' => '08:00',
            'check_out' => '17:00',
            'status' => 'present',
        ]);

        $attendanceB = Attendance::create([
            'tenant_id' => $this->tenantB->id,
            'employee_id' => $this->employeeB->id,
            'date' => '2026-04-11',
            'check_in' => '08:30',
            'check_out' => '17:30',
            'status' => 'present',
        ]);

        $response = $this->actingAs($manager)->get(route('attendances.index', ['tenant_id' => $this->tenantB->id]));

        $response->assertOk();
        $response->assertSee($this->employeeA->name);
        $response->assertDontSee($this->employeeB->name);

        $this->actingAs($manager)->get(route('attendances.create'))->assertOk();

        $this->actingAs($manager)->post(route('attendances.store'), [
            'tenant_id' => $this->tenantB->id,
            'employee_id' => $this->employeeA->id,
            'work_location_id' => $this->workLocationA->id,
            'date' => '2026-04-12',
            'check_in' => '08:15',
            'check_out' => '17:15',
            'status' => 'present',
            'latitude' => -6.2001500,
            'longitude' => 106.8167500,
        ])->assertRedirect(route('attendances.index'));

        $this->assertDatabaseHas('attendances', [
            'tenant_id' => $this->tenantA->id,
            'employee_id' => $this->employeeA->id,
        ]);
        $this->assertNotNull(
            Attendance::query()
                ->where('tenant_id', $this->tenantA->id)
                ->where('employee_id', $this->employeeA->id)
                ->whereDate('date', '2026-04-12')
                ->first()
        );

        $this->actingAs($manager)->get(route('attendances.edit', $attendanceA))->assertOk();
        $this->actingAs($manager)->get(route('attendances.edit', $attendanceB))->assertForbidden();

        $this->actingAs($manager)->put(route('attendances.update', $attendanceA), [
            'tenant_id' => $this->tenantB->id,
            'employee_id' => $this->employeeA->id,
            'work_location_id' => $this->workLocationA->id,
            'date' => '2026-04-13',
            'check_in' => '09:00',
            'check_out' => '18:00',
            'status' => 'late',
            'latitude' => -6.2001000,
            'longitude' => 106.8169000,
        ])->assertRedirect(route('attendances.index'));

        $this->assertDatabaseHas('attendances', [
            'id' => $attendanceA->id,
            'tenant_id' => $this->tenantA->id,
            'status' => 'late',
        ]);
        $this->assertSame('2026-04-13', $attendanceA->fresh()->date->format('Y-m-d'));

        $this->actingAs($manager)->put(route('attendances.update', $attendanceB), [
            'tenant_id' => $this->tenantA->id,
            'employee_id' => $this->employeeB->id,
            'work_location_id' => $this->workLocationB->id,
            'date' => '2026-04-14',
            'check_in' => '09:15',
            'check_out' => '18:15',
            'status' => 'late',
            'latitude' => -6.9149000,
            'longitude' => 107.6099000,
        ])->assertForbidden();

        $this->actingAs($manager)->delete(route('attendances.destroy', $attendanceA))->assertForbidden();
    }

    public function test_employee_can_only_view_own_attendance_and_cannot_crud(): void
    {
        $employeeUser = $this->makeUser('employee', $this->tenantA, 'attendance-employee-a@example.test');

        $this->employeeA->update([
            'user_id' => $employeeUser->id,
        ]);

        $ownAttendance = Attendance::create([
            'tenant_id' => $this->tenantA->id,
            'employee_id' => $this->employeeA->id,
            'date' => '2026-04-15',
            'check_in' => '08:00',
            'check_out' => '17:00',
            'status' => 'present',
        ]);

        $otherAttendance = Attendance::create([
            'tenant_id' => $this->tenantB->id,
            'employee_id' => $this->employeeB->id,
            'date' => '2026-04-16',
            'check_in' => '08:00',
            'check_out' => '17:00',
            'status' => 'present',
        ]);

        $response = $this->actingAs($employeeUser)->get(route('attendances.index'));

        $response->assertOk();
        $response->assertSee($this->employeeA->name);
        $response->assertDontSee($this->employeeB->name);

        $this->actingAs($employeeUser)->get(route('attendances.create'))->assertForbidden();
        $this->actingAs($employeeUser)->post(route('attendances.store'), [
            'tenant_id' => $this->tenantA->id,
            'employee_id' => $this->employeeA->id,
            'work_location_id' => $this->workLocationA->id,
            'date' => '2026-04-17',
            'check_in' => '08:00',
            'check_out' => '17:00',
            'status' => 'present',
            'latitude' => -6.2001500,
            'longitude' => 106.8167500,
        ])->assertForbidden();
        $this->actingAs($employeeUser)->get(route('attendances.edit', $ownAttendance))->assertForbidden();
        $this->actingAs($employeeUser)->put(route('attendances.update', $ownAttendance), [
            'tenant_id' => $this->tenantA->id,
            'employee_id' => $this->employeeA->id,
            'work_location_id' => $this->workLocationA->id,
            'date' => '2026-04-18',
            'check_in' => '08:30',
            'check_out' => '17:30',
            'status' => 'late',
            'latitude' => -6.2001500,
            'longitude' => 106.8167500,
        ])->assertForbidden();
        $this->actingAs($employeeUser)->delete(route('attendances.destroy', $ownAttendance))->assertForbidden();

        $this->assertDatabaseHas('attendances', ['id' => $ownAttendance->id]);
        $this->assertDatabaseHas('attendances', ['id' => $otherAttendance->id]);
    }

    protected function makeUser(string $role, Tenant $tenant, string $email): User
    {
        return User::create([
            'tenant_id' => $tenant->id,
            'name' => ucfirst($role).' Attendance User',
            'email' => $email,
            'password' => 'password',
            'role' => $role,
            'status' => 'active',
        ]);
    }
}