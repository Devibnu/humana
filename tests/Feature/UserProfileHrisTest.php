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

class UserProfileHrisTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_access_hris_profile_and_see_account_employee_and_summary_data(): void
    {
        $tenant = Tenant::create([
            'name' => 'HRIS Profile Tenant',
            'slug' => 'hris-profile-tenant',
            'domain' => 'hris-profile-tenant.test',
            'status' => 'active',
        ]);

        $department = Department::create([
            'tenant_id' => $tenant->id,
            'name' => 'Operations',
            'status' => 'active',
        ]);

        $position = Position::create([
            'tenant_id' => $tenant->id,
            'name' => 'Operations Lead',
            'status' => 'active',
        ]);

        $workLocation = WorkLocation::create([
            'tenant_id' => $tenant->id,
            'name' => 'Operations Hub',
            'address' => 'Bandung',
            'latitude' => -6.9,
            'longitude' => 107.6,
            'radius' => 180,
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'HRIS Employee User',
            'email' => 'hris-employee-user@example.test',
            'password' => 'password',
            'role' => 'employee',
            'status' => 'active',
        ]);

        $employee = Employee::create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'employee_code' => 'EMP-HRIS-001',
            'name' => 'HRIS Employee',
            'email' => 'hris-employee@example.test',
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

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => now()->startOfWeek()->addDays(1)->toDateString(),
            'end_date' => now()->startOfWeek()->addDays(1)->toDateString(),
            'reason' => 'HRIS leave summary',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
        $response->assertViewIs('profile.index');
        $response->assertSee('Humana HRIS Profile');
        $response->assertSee('Account Information');
        $response->assertSee('Employee Information');
        $response->assertSee('Personal Attendance and Leave Summary');
        $response->assertSee('HRIS Employee User');
        $response->assertSee('hris-employee-user@example.test');
        $response->assertSee('EMP-HRIS-001');
        $response->assertSee('Operations');
        $response->assertSee('Operations Lead');
        $response->assertSee('Operations Hub');
        $response->assertDontSee('Platform Settings');
        $response->assertDontSee('Conversations');
        $response->assertDontSee('Projects');

        $summary = $response->viewData('weeklyAttendanceSummary');

        $this->assertSame(1, $summary['present']);
        $this->assertSame(0, $summary['absent']);
        $this->assertSame(1, $summary['leave']);
    }
}