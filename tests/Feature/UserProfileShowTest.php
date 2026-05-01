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

class UserProfileShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_information_is_visible_on_user_detail_page(): void
    {
        $tenant = $this->makeTenant('show-account');
        $admin = $this->makeUser('admin_hr', $tenant, 'admin-show-account@example.test', 'Admin Show');
        $managedUser = $this->makeUser('employee', $tenant, 'managed-show-account@example.test', 'Managed Detail');

        $response = $this->actingAs($admin)->get(route('users.show-profile', $managedUser));

        $response->assertOk();
        $response->assertViewIs('user.profile.show');
        $response->assertSee('card mx-4 mb-4 shadow-xs', false);
        $response->assertSee('Account Information');
        $response->assertSee('Managed Detail');
        $response->assertSee('managed-show-account@example.test');
        $response->assertSee('Employee');
        $response->assertSee($tenant->name);
        $response->assertSee('Active');
    }

    public function test_linked_employee_information_is_visible_when_user_has_employee_relation(): void
    {
        [$admin, $managedUser, $employee] = $this->createLinkedUserContext('show-linked');

        $response = $this->actingAs($admin)->get(route('users.show-profile', $managedUser));

        $response->assertOk();
        $response->assertSee('Linked Employee');
        $response->assertSee($employee->employee_code);
        $response->assertSee($employee->department->name);
        $response->assertSee($employee->position->name);
        $response->assertSee($employee->workLocation->name);
        $response->assertDontSee('Employee record not linked');
    }

    public function test_empty_state_is_visible_when_user_has_no_linked_employee(): void
    {
        $tenant = $this->makeTenant('show-empty');
        $admin = $this->makeUser('admin_hr', $tenant, 'admin-show-empty@example.test', 'Admin Empty');
        $managedUser = $this->makeUser('employee', $tenant, 'managed-show-empty@example.test', 'Managed Empty');

        $response = $this->actingAs($admin)->get(route('users.show-profile', $managedUser));

        $response->assertOk();
        $response->assertSee('Employee record not linked');
        $response->assertSee('Link Employee');
    }

    public function test_admin_sees_detail_action_buttons(): void
    {
        $tenant = $this->makeTenant('show-actions');
        $admin = $this->makeUser('admin_hr', $tenant, 'admin-show-actions@example.test', 'Admin Actions');
        $managedUser = $this->makeUser('employee', $tenant, 'managed-show-actions@example.test', 'Managed Actions');

        $response = $this->actingAs($admin)->get(route('users.show-profile', $managedUser));

        $response->assertOk();
        $response->assertSee(route('users.profile-edit', $managedUser), false);
        $response->assertSee(route('users.show-profile.destroy', $managedUser), false);
        $response->assertSee(route('users.index'), false);
        $response->assertSee('fas fa-edit', false);
        $response->assertSee('fas fa-trash', false);
        $response->assertSee('fas fa-arrow-left', false);
    }

    public function test_manager_and_employee_cannot_access_user_detail_page(): void
    {
        $tenant = $this->makeTenant('show-rbac');
        $manager = $this->makeUser('manager', $tenant, 'manager-show@example.test', 'Manager Show');
        $employeeViewer = $this->makeUser('employee', $tenant, 'employee-show@example.test', 'Employee Show');
        $managedUser = $this->makeUser('employee', $tenant, 'managed-show@example.test', 'Managed RBAC');

        $this->actingAs($manager)
            ->get(route('users.show-profile', $managedUser))
            ->assertForbidden();

        $this->actingAs($employeeViewer)
            ->get(route('users.show-profile', $managedUser))
            ->assertForbidden();
    }

    protected function createLinkedUserContext(string $slug): array
    {
        $tenant = $this->makeTenant($slug);
        $admin = $this->makeUser('admin_hr', $tenant, "admin-{$slug}@example.test", 'Admin Linked');
        $managedUser = $this->makeUser('employee', $tenant, "managed-{$slug}@example.test", 'Managed Linked');

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

        $employee = Employee::create([
            'user_id' => $managedUser->id,
            'tenant_id' => $tenant->id,
            'employee_code' => 'EMP-SHOW-001',
            'name' => 'Managed Linked Employee',
            'email' => "employee-{$slug}@example.test",
            'position_id' => $position->id,
            'department_id' => $department->id,
            'work_location_id' => $workLocation->id,
            'status' => 'active',
        ]);

        return [$admin, $managedUser, $employee->load(['department', 'position', 'workLocation'])];
    }

    protected function makeTenant(string $slug): Tenant
    {
        return Tenant::create([
            'name' => ucfirst(str_replace('-', ' ', $slug)).' Tenant',
            'slug' => $slug,
            'domain' => $slug.'.test',
            'status' => 'active',
        ]);
    }

    protected function makeUser(string $role, Tenant $tenant, string $email, string $name): User
    {
        return User::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'email' => $email,
            'password' => 'password123',
            'role' => $role,
            'status' => 'active',
        ]);
    }
}