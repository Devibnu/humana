<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsersCrudRbacTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;

    protected Tenant $tenantB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::create([
            'name' => 'Users Crud Tenant A',
            'slug' => 'users-crud-tenant-a',
            'domain' => 'users-crud-tenant-a.test',
            'status' => 'active',
        ]);

        $this->tenantB = Tenant::create([
            'name' => 'Users Crud Tenant B',
            'slug' => 'users-crud-tenant-b',
            'domain' => 'users-crud-tenant-b.test',
            'status' => 'active',
        ]);
    }

    public function test_admin_hr_can_store_update_and_destroy_user(): void
    {
        $admin = $this->makeUser('admin_hr', $this->tenantA, 'admin-users-crud@example.test', 'Admin Users Crud');

        $this->actingAs($admin)->post(route('users.store'), [
            'tenant_id'             => $this->tenantA->id,
            'name'                  => 'Created User',
            'email'                 => 'created-user@example.test',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role'                  => 'employee',
            'status'                => 'active',
        ])->assertRedirect(route('users.index'));

        $createdUser = User::where('email', 'created-user@example.test')->firstOrFail();

        $this->assertDatabaseHas('users', [
            'id' => $createdUser->id,
            'tenant_id' => $this->tenantA->id,
            'name' => 'Created User',
            'role' => 'employee',
            'status' => 'active',
        ]);

        $this->actingAs($admin)->put(route('users.update', $createdUser), [
            'tenant_id' => $this->tenantB->id,
            'name' => 'Updated User',
            'email' => 'updated-user@example.test',
            'password' => '',
            'role' => 'manager',
            'status' => 'inactive',
        ])->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $createdUser->id,
            'tenant_id' => $this->tenantB->id,
            'name' => 'Updated User',
            'email' => 'updated-user@example.test',
            'role' => 'manager',
            'status' => 'inactive',
        ]);

        $this->actingAs($admin)
            ->delete(route('users.destroy', $createdUser))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseMissing('users', [
            'id' => $createdUser->id,
        ]);
    }

    public function test_manager_cannot_store_update_or_destroy_user(): void
    {
        $manager = $this->makeUser('manager', $this->tenantA, 'manager-users-crud@example.test', 'Manager Users Crud');
        $editableUser = $this->makeUser('employee', $this->tenantA, 'employee-users-crud@example.test', 'Employee Users Crud');

        $this->actingAs($manager)->post(route('users.store'), [
            'tenant_id' => $this->tenantA->id,
            'name' => 'Manager Created User',
            'email' => 'manager-created-user@example.test',
            'password' => 'password123',
            'role' => 'employee',
            'status' => 'active',
        ])->assertForbidden();

        $this->actingAs($manager)->put(route('users.update', $editableUser), [
            'tenant_id' => $this->tenantA->id,
            'name' => 'Manager Updated User',
            'email' => 'manager-updated-user@example.test',
            'password' => '',
            'role' => 'employee',
            'status' => 'inactive',
        ])->assertForbidden();

        $this->actingAs($manager)
            ->delete(route('users.destroy', $editableUser))
            ->assertForbidden();

        $this->assertDatabaseMissing('users', [
            'email' => 'manager-created-user@example.test',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $editableUser->id,
            'name' => 'Employee Users Crud',
            'email' => 'employee-users-crud@example.test',
            'status' => 'active',
        ]);
    }

    public function test_employee_cannot_access_user_form_or_submit_crud(): void
    {
        $employee = $this->makeUser('employee', $this->tenantA, 'employee-users-rbac@example.test', 'Employee Users Rbac');
        $editableUser = $this->makeUser('manager', $this->tenantA, 'manager-editable-users-rbac@example.test', 'Manager Editable');

        $this->actingAs($employee)->get(route('users.create'))->assertForbidden();
        $this->actingAs($employee)->get(route('users.edit', $editableUser))->assertForbidden();

        $this->actingAs($employee)->post(route('users.store'), [
            'tenant_id' => $this->tenantA->id,
            'name' => 'Employee Created User',
            'email' => 'employee-created-user@example.test',
            'password' => 'password123',
            'role' => 'employee',
            'status' => 'active',
        ])->assertForbidden();

        $this->actingAs($employee)->put(route('users.update', $editableUser), [
            'tenant_id' => $this->tenantA->id,
            'name' => 'Employee Updated User',
            'email' => 'employee-updated-user@example.test',
            'password' => '',
            'role' => 'manager',
            'status' => 'inactive',
        ])->assertForbidden();

        $this->actingAs($employee)
            ->delete(route('users.destroy', $editableUser))
            ->assertForbidden();
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