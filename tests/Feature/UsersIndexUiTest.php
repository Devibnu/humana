<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsersIndexUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_does_not_see_user_crud_actions_and_sees_tenant_scope_indicator(): void
    {
        $tenant = Tenant::create([
            'name' => 'Scope Tenant',
            'slug' => 'scope-tenant',
            'domain' => 'scope-tenant.test',
            'status' => 'active',
        ]);

        $manager = $this->makeUser('manager', $tenant, 'manager-ui@example.test', 'Manager UI');
        $listedUser = $this->makeUser('employee', $tenant, 'employee-ui@example.test', 'Employee UI');

        $response = $this->actingAs($manager)->get(route('users.index'));

        $response->assertOk();
        $response->assertSee('Tenant scope active: you are only viewing users from '.$tenant->name.'.');
        $response->assertDontSee(route('users.create'), false);
        $response->assertDontSee(route('users.show-profile', $listedUser), false);
        $response->assertDontSee(route('users.profile-edit', $listedUser), false);
        $response->assertDontSee(route('users.destroy', $listedUser), false);
        $response->assertDontSee('<i class="fas fa-trash"></i>', false);
        $response->assertDontSee('Action');
    }

    public function test_employee_cannot_see_delete_action_from_users_index(): void
    {
        $tenant = Tenant::create([
            'name' => 'Employee Scope Tenant',
            'slug' => 'employee-scope-tenant',
            'domain' => 'employee-scope-tenant.test',
            'status' => 'active',
        ]);

        $employee = $this->makeUser('employee', $tenant, 'employee-no-delete@example.test', 'Employee No Delete');

        $this->actingAs($employee)
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_admin_hr_sees_user_crud_actions(): void
    {
        $tenant = Tenant::create([
            'name' => 'Admin Tenant',
            'slug' => 'admin-tenant',
            'domain' => 'admin-tenant.test',
            'status' => 'active',
        ]);

        $admin = $this->makeUser('admin_hr', $tenant, 'admin-ui@example.test', 'Admin UI');
        $listedUser = $this->makeUser('employee', $tenant, 'listed-admin-ui@example.test', 'Listed User');

        $response = $this->actingAs($admin)->get(route('users.index'));

        $response->assertOk();
        $response->assertSee(route('users.create'), false);
        $response->assertSee(route('users.show-profile', $listedUser), false);
        $response->assertSee(route('users.profile-edit', $listedUser), false);
        $response->assertSee(route('users.destroy', $listedUser), false);
        $response->assertSee('fas fa-eye text-info', false);
        $response->assertSee('fas fa-edit text-secondary', false);
        $response->assertSee('fas fa-trash text-danger', false);
        $response->assertSee('Action');
    }

    protected function makeUser(string $role, Tenant $tenant, string $email, string $name): User
    {
        return User::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'email' => $email,
            'password' => 'password',
            'role' => $role,
            'status' => 'active',
        ]);
    }
}