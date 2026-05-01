<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkSchedulesMasterTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_work_schedule_and_employee_form_can_select_it(): void
    {
        [$tenant, $admin] = $this->makeAdminContext();

        $response = $this->actingAs($admin)->post(route('work-schedules.store'), [
            'tenant_id' => $tenant->id,
            'name' => 'Shift Tengah',
            'code' => 'shift_tengah',
            'check_in_time' => '10:00',
            'check_out_time' => '18:00',
            'late_tolerance_minutes' => 5,
            'early_leave_tolerance_minutes' => 10,
            'status' => 'active',
            'sort_order' => 50,
        ]);

        $response->assertRedirect(route('work-schedules.index'));

        $schedule = WorkSchedule::where('code', 'shift_tengah')->firstOrFail();

        $this->assertDatabaseHas('work_schedules', [
            'id' => $schedule->id,
            'tenant_id' => $tenant->id,
            'name' => 'Shift Tengah',
            'late_tolerance_minutes' => 5,
            'early_leave_tolerance_minutes' => 10,
        ]);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'SCH-001',
            'name' => 'Schedule Employee',
            'email' => 'schedule-employee@example.test',
            'role' => 'staff',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('employees.edit', $employee))
            ->assertOk()
            ->assertSee('Jadwal Kerja')
            ->assertSee('Shift Tengah (10:00 - 18:00)');

        $this->actingAs($admin)
            ->put(route('employees.update', $employee), [
                'tenant_id' => $tenant->id,
                'employee_code' => $employee->employee_code,
                'name' => $employee->name,
                'email' => $employee->email,
                'role' => 'staff',
                'status' => 'active',
                'work_schedule_id' => $schedule->id,
            ])
            ->assertRedirect(route('employees.index'));

        $this->assertSame($schedule->id, $employee->fresh()->work_schedule_id);
    }

    public function test_work_schedule_cannot_be_deleted_when_used_by_employee(): void
    {
        [$tenant, $admin] = $this->makeAdminContext();
        $schedule = WorkSchedule::where('tenant_id', $tenant->id)->where('code', 'office_hour')->firstOrFail();

        Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'SCH-002',
            'name' => 'Used Schedule Employee',
            'email' => 'used-schedule-employee@example.test',
            'role' => 'staff',
            'status' => 'active',
            'work_schedule_id' => $schedule->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('work-schedules.destroy', $schedule))
            ->assertRedirect(route('work-schedules.index'))
            ->assertSessionHasErrors('work_schedule');

        $this->assertDatabaseHas('work_schedules', ['id' => $schedule->id]);
    }

    protected function makeAdminContext(): array
    {
        $tenant = Tenant::create([
            'name' => 'Schedule Tenant',
            'slug' => 'schedule-tenant',
            'domain' => 'schedule-tenant.test',
            'status' => 'active',
        ]);

        $role = Role::firstOrCreate(
            ['name' => 'Admin HR'],
            ['description' => 'Admin HR']
        );

        foreach (['work_schedules', 'employees'] as $menuKey) {
            RolePermission::updateOrCreate([
                'role_id' => $role->id,
                'menu_key' => $menuKey,
            ], [
                'can_access' => true,
            ]);
        }

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Schedule Admin',
            'email' => 'schedule-admin@example.test',
            'password' => 'password',
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        return [$tenant, $admin];
    }
}
