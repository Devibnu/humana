<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsersActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_edit_and_delete_user_actions(): void
    {
        $tenant = $this->makeTenant('actions-admin');
        $admin = $this->makeUser('admin_hr', $tenant, 'admin-actions@example.test', 'Admin Actions');
        $managedUser = $this->makeUser('employee', $tenant, 'managed-user@example.test', 'Managed User');

        $response = $this->actingAs($admin)->get(route('users.index'));

        $response->assertOk();
        $response->assertSee(route('users.show-profile', $managedUser), false);
        $response->assertSee(route('users.profile-edit', $managedUser), false);
        $response->assertSee(route('users.destroy', $managedUser), false);
        $response->assertSee('<i class="fas fa-eye text-info"></i>', false);
        $response->assertSee('<i class="fas fa-edit text-secondary"></i>', false);
        $response->assertSee('<i class="fas fa-trash text-danger"></i>', false);

        $this->actingAs($admin)
            ->get(route('users.show-profile', $managedUser))
            ->assertOk()
            ->assertSee('Managed User')
            ->assertSee('Employee record not linked');

        $this->actingAs($admin)
            ->get(route('users.profile-edit', $managedUser))
            ->assertOk()
            ->assertSee('Edit User');

        $this->actingAs($admin)
            ->put(route('users.update', $managedUser), [
                'tenant_id' => $tenant->id,
                'name' => 'Managed User Updated',
                'email' => 'managed-user-updated@example.test',
                'password' => '',
                'role' => 'employee',
                'status' => 'inactive',
            ])
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $managedUser->id,
            'name' => 'Managed User Updated',
            'email' => 'managed-user-updated@example.test',
            'status' => 'inactive',
        ]);

        $this->actingAs($admin)
            ->delete(route('users.destroy', $managedUser))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseMissing('users', [
            'id' => $managedUser->id,
        ]);
    }

    public function test_manager_and_employee_cannot_delete_user(): void
    {
        $tenant = $this->makeTenant('actions-tenant');

        $manager = $this->makeUser('manager', $tenant, 'manager-actions@example.test', 'Manager Actions');
        $employee = $this->makeUser('employee', $tenant, 'employee-actions@example.test', 'Employee Actions');
        $managedUser = $this->makeUser('employee', $tenant, 'managed-actions@example.test', 'Managed Actions');

        $this->actingAs($manager)->delete(route('users.destroy', $managedUser))->assertForbidden();

        $this->actingAs($employee)->delete(route('users.destroy', $managedUser))->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $managedUser->id,
        ]);
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