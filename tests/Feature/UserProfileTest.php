<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_logged_in_user_can_view_user_profile(): void
    {
        $tenant = Tenant::create([
            'name' => 'Profile Tenant',
            'slug' => 'profile-tenant',
            'domain' => 'profile-tenant.test',
            'status' => 'active',
        ]);

        $department = Department::create([
            'tenant_id' => $tenant->id,
            'name' => 'Human Resources',
            'status' => 'active',
        ]);

        $position = Position::create([
            'tenant_id' => $tenant->id,
            'name' => 'HR Generalist',
            'status' => 'active',
        ]);

        $workLocation = WorkLocation::create([
            'tenant_id' => $tenant->id,
            'name' => 'Main Office',
            'address' => 'Jakarta',
            'latitude' => -6.2000000,
            'longitude' => 106.8166667,
            'radius' => 100,
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Profile User',
            'email' => 'profile-user@example.test',
            'password' => 'password',
            'role' => 'employee',
            'status' => 'active',
        ]);

        Employee::create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'employee_code' => 'EMP-PR-001',
            'name' => 'Profile Employee',
            'email' => 'profile-employee@example.test',
            'position_id' => $position->id,
            'department_id' => $department->id,
            'work_location_id' => $workLocation->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
        $response->assertViewIs('profile.index');
        $response->assertSee('Profile User');
        $response->assertSee('profile-user@example.test');
        $response->assertSee('Employee Information');
        $response->assertSee('EMP-PR-001');
        $response->assertSee('Human Resources');
        $response->assertSee('HR Generalist');
        $response->assertSee('Main Office');

        $viewUser = $response->viewData('user');
        $viewEmployee = $response->viewData('employee');

        $this->assertSame($user->id, $viewUser->id);
        $this->assertSame('EMP-PR-001', $viewEmployee->employee_code);
    }

    public function test_guest_is_redirected_to_login_when_accessing_user_profile(): void
    {
        $this->get('/profile')->assertRedirect('/login');
    }
}