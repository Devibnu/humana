<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DependencyAlignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_backed_modules_remain_valid_after_seed(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'admin@humana.test')->firstOrFail();
        $employeeUser = User::where('email', 'employee@humana.test')->firstOrFail();
        $employee = Employee::where('user_id', $employeeUser->id)->firstOrFail();

        Attendance::create([
            'tenant_id' => $employee->tenant_id,
            'employee_id' => $employee->id,
            'date' => '2026-04-17',
            'check_in' => '08:00:00',
            'check_out' => '17:00:00',
            'status' => 'present',
        ]);

        Leave::create([
            'tenant_id' => $employee->tenant_id,
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => '2026-04-17',
            'end_date' => '2026-04-18',
            'reason' => 'Dependency alignment leave',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)->get(route('users.index'))->assertOk();
        $this->actingAs($admin)->get(route('employees.index'))->assertOk();
        $this->actingAs($admin)->get(route('employees.create'))->assertOk()->assertSee('employee@humana.test');
        $this->actingAs($employeeUser)->get(route('profile'))->assertOk();
        $this->actingAs($employeeUser)->get(route('attendances.index'))->assertForbidden();
        $this->actingAs($employeeUser)->get(route('leaves.index'))->assertForbidden();
        $this->actingAs($employeeUser)->get(route('employees.leaves.show', $employee))->assertForbidden();
    }

    public function test_modules_do_not_fail_after_migrate_fresh_seed_restores_users_table(): void
    {
        Artisan::call('migrate:fresh', [
            '--seed' => true,
            '--force' => true,
        ]);

        $admin = User::where('email', 'admin@humana.test')->firstOrFail();
        $employeeUser = User::where('email', 'employee@humana.test')->firstOrFail();
        $employee = Employee::where('user_id', $employeeUser->id)->firstOrFail();

        $this->actingAs($admin)->get(route('users.index'))->assertOk();
        $this->actingAs($admin)->get(route('employees.index'))->assertOk();
        $this->actingAs($employeeUser)->get(route('profile'))->assertOk();
        $this->actingAs($employeeUser)->get(route('attendances.index'))->assertForbidden();
        $this->actingAs($employeeUser)->get(route('leaves.index'))->assertForbidden();
        $this->assertTrue($employeeUser->relationLoaded('employee') ? true : $employeeUser->employee()->exists());
        $this->assertSame($employeeUser->id, $employee->user_id);
    }
}