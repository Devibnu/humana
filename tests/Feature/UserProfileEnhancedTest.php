<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Position;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProfileEnhancedTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_profile_shows_account_employee_and_weekly_attendance_summary(): void
    {
        $tenant = Tenant::create([
            'name' => 'Enhanced Profile Tenant',
            'slug' => 'enhanced-profile-tenant',
            'domain' => 'enhanced-profile-tenant.test',
            'status' => 'active',
        ]);

        $department = Department::create([
            'tenant_id' => $tenant->id,
            'name' => 'People Operations',
            'status' => 'active',
        ]);

        $position = Position::create([
            'tenant_id' => $tenant->id,
            'name' => 'People Analyst',
            'status' => 'active',
        ]);

        $workLocation = WorkLocation::create([
            'tenant_id' => $tenant->id,
            'name' => 'HQ Building',
            'address' => 'Jakarta',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'radius' => 150,
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Enhanced Employee User',
            'email' => 'enhanced-employee-user@example.test',
            'password' => 'password',
            'role' => 'employee',
            'status' => 'active',
        ]);

        $employee = Employee::create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'employee_code' => 'EMP-ENH-001',
            'name' => 'Enhanced Employee',
            'email' => 'enhanced-employee@example.test',
            'position_id' => $position->id,
            'department_id' => $department->id,
            'work_location_id' => $workLocation->id,
            'status' => 'active',
        ]);

        Attendance::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'date' => now()->startOfWeek()->toDateString(),
            'status' => 'present',
        ]);

        Attendance::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'date' => now()->startOfWeek()->addDay()->toDateString(),
            'status' => 'absent',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => now()->startOfWeek()->addDays(2)->toDateString(),
            'end_date' => now()->startOfWeek()->addDays(3)->toDateString(),
            'reason' => 'Personal leave',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($user)->get('/user-profile');

        $response->assertOk();
        $response->assertViewIs('profile.index');
        $response->assertSee('Account Information');
        $response->assertSee('Enhanced Employee User');
        $response->assertSee('enhanced-employee-user@example.test');
        $response->assertSee('Employee Information');
        $response->assertSee('EMP-ENH-001');
        $response->assertSee('People Operations');
        $response->assertSee('People Analyst');
        $response->assertSee('HQ Building');
        $response->assertSee('Personal Attendance and Leave Summary');
        $response->assertSee('Leave This Week');
        $response->assertSee('Edit Profile');

        $weeklyAttendanceSummary = $response->viewData('weeklyAttendanceSummary');

        $this->assertSame(1, $weeklyAttendanceSummary['present']);
        $this->assertSame(1, $weeklyAttendanceSummary['absent']);
        $this->assertSame(1, $weeklyAttendanceSummary['leave']);
    }

    public function test_employee_profile_shows_informative_empty_state_when_not_linked(): void
    {
        $tenant = Tenant::create([
            'name' => 'Enhanced Empty Tenant',
            'slug' => 'enhanced-empty-tenant',
            'domain' => 'enhanced-empty-tenant.test',
            'status' => 'active',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Unlinked Employee User',
            'email' => 'unlinked-employee-user@example.test',
            'password' => 'password',
            'role' => 'employee',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get('/user-profile');

        $response->assertOk();
        $response->assertViewIs('profile.index');
        $response->assertSee('Employee data not linked yet');
        $response->assertSee('Attendance summary unavailable');
        $this->assertNull($response->viewData('weeklyAttendanceSummary'));
    }
}