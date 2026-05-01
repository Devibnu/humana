<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserProfileEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_name_email_password_and_avatar(): void
    {
        Storage::fake('public');

        [$user] = $this->createProfileContext();

        $avatar = UploadedFile::fake()->image('avatar.png');

        $response = $this->actingAs($user)->put('/user-profile', [
            'name' => 'Updated Profile User',
            'email' => 'updated-profile-user@example.test',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
            'avatar' => $avatar,
        ]);

        $response->assertRedirect('/user-profile/edit');

        $user->refresh();

        $this->assertSame('Updated Profile User', $user->name);
        $this->assertSame('updated-profile-user@example.test', $user->email);
        $this->assertTrue(Hash::check('new-password-123', $user->password));
        $this->assertNotNull($user->avatar_path);
        Storage::disk('public')->assertExists($user->avatar_path);
    }

    public function test_edit_profile_shows_readonly_employee_information(): void
    {
        [$user, $employee] = $this->createProfileContext();

        $response = $this->actingAs($user)->get('/user-profile/edit');

        $response->assertOk();
        $response->assertViewIs('user.profile.edit');
        $response->assertSee('Account Information');
        $response->assertSee('Employee Information');
        $response->assertSee('data-testid="employee-nik"', false);
        $response->assertSee('data-testid="employee-department"', false);
        $response->assertSee('data-testid="employee-position"', false);
        $response->assertSee('data-testid="employee-work-location"', false);
        $response->assertSee('readonly', false);
        $response->assertSee($employee->employee_code);
        $response->assertSee($employee->department->name);
        $response->assertSee($employee->position->name);
        $response->assertSee($employee->workLocation->name);
    }

    public function test_guest_is_redirected_to_login_for_edit_profile_routes(): void
    {
        $this->get('/user-profile/edit')->assertRedirect('/login');
        $this->put('/user-profile', [
            'name' => 'Guest User',
            'email' => 'guest@example.test',
        ])->assertRedirect('/login');
    }

    public function test_link_employee_record_button_is_only_visible_for_admin_without_employee_relation(): void
    {
        $tenant = Tenant::create([
            'name' => 'Admin Link Tenant',
            'slug' => 'admin-link-tenant',
            'domain' => 'admin-link-tenant.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin HR User',
            'email' => 'admin-link@example.test',
            'password' => 'password123',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $employeeUser = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Employee User',
            'email' => 'employee-link@example.test',
            'password' => 'password123',
            'role' => 'employee',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get('/user-profile/edit')
            ->assertOk()
            ->assertSee('Link Employee Record')
            ->assertSee('/employees/create?user_id='.$admin->id.'&amp;tenant_id='.$admin->tenant_id, false);

        $this->actingAs($employeeUser)
            ->get('/user-profile/edit')
            ->assertOk()
            ->assertDontSee('Link Employee Record');
    }

    public function test_edit_profile_is_full_wide_layout(): void
    {
        [$user] = $this->createProfileContext();

        $response = $this->actingAs($user)->get('/user-profile/edit');

        $response->assertOk();
        // Full-width outer wrapper
        $response->assertSee('col-12', false);
        $response->assertSee('card mx-4 mb-4', false);
        // Header in card-header
        $response->assertSee('Edit Profile');
        $response->assertSee('Back to Profile');
        // Two-column structure
        $response->assertSee('col-lg-6', false);
        // Action buttons right-aligned
        $response->assertSee('justify-content-end', false);
        $response->assertSee('Save Changes');
        $response->assertSee('Cancel');
        // Password side-by-side
        $response->assertSee('name="password"', false);
        $response->assertSee('name="password_confirmation"', false);
    }

    protected function createProfileContext(): array
    {
        $tenant = Tenant::create([
            'name' => 'Edit Profile Tenant',
            'slug' => 'edit-profile-tenant',
            'domain' => 'edit-profile-tenant.test',
            'status' => 'active',
        ]);

        $department = Department::create([
            'tenant_id' => $tenant->id,
            'name' => 'People Operations',
            'status' => 'active',
        ]);

        $position = Position::create([
            'tenant_id' => $tenant->id,
            'name' => 'HR Specialist',
            'status' => 'active',
        ]);

        $workLocation = WorkLocation::create([
            'tenant_id' => $tenant->id,
            'name' => 'Jakarta HQ',
            'address' => 'Jakarta',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'radius' => 100,
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Profile Editor',
            'email' => 'profile-editor@example.test',
            'password' => 'password123',
            'role' => 'employee',
            'status' => 'active',
        ]);

        $employee = Employee::create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'employee_code' => 'EMP-EDIT-001',
            'name' => 'Profile Editor Employee',
            'email' => 'profile-editor-employee@example.test',
            'position_id' => $position->id,
            'department_id' => $department->id,
            'work_location_id' => $workLocation->id,
            'status' => 'active',
        ]);

        return [$user, $employee];
    }
}