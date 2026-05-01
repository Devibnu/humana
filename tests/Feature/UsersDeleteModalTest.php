<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsersDeleteModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_delete_modal_markup_is_present_for_admin(): void
    {
        $tenant = $this->makeTenant('delete-modal');
        $admin = $this->makeUser('admin_hr', $tenant, 'admin-delete-modal@example.test', 'Admin Delete Modal');
        $managedUser = $this->makeUser('employee', $tenant, 'managed-delete-modal@example.test', 'Managed Delete Modal');

        $response = $this->actingAs($admin)->get(route('users.index'));

        $response->assertOk();
        $response->assertSee('data-bs-target="#deleteUserModal-'.$managedUser->id.'"', false);
        $response->assertSee('id="deleteUserModal-'.$managedUser->id.'"', false);
        $response->assertSee('Konfirmasi Hapus User');
        $response->assertSee('Apakah Anda yakin ingin menghapus user ini?');
        $response->assertSee('Confirm Delete');
        $response->assertSee('Cancel');
    }

    public function test_user_is_deleted_after_confirm_delete(): void
    {
        $tenant = $this->makeTenant('delete-confirm');
        $admin = $this->makeUser('admin_hr', $tenant, 'admin-delete-confirm@example.test', 'Admin Delete Confirm');
        $managedUser = $this->makeUser('employee', $tenant, 'managed-delete-confirm@example.test', 'Managed Delete Confirm');

        $this->actingAs($admin)
            ->delete(route('users.destroy', $managedUser))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseMissing('users', [
            'id' => $managedUser->id,
        ]);

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertSee('User berhasil dihapus');
    }

    public function test_cancel_does_not_delete_user(): void
    {
        $tenant = $this->makeTenant('delete-cancel');
        $admin = $this->makeUser('admin_hr', $tenant, 'admin-delete-cancel@example.test', 'Admin Delete Cancel');
        $managedUser = $this->makeUser('employee', $tenant, 'managed-delete-cancel@example.test', 'Managed Delete Cancel');

        $response = $this->actingAs($admin)->get(route('users.index'));

        $response->assertOk();
        $response->assertSee('id="deleteUserModal-'.$managedUser->id.'"', false);

        $this->assertDatabaseHas('users', [
            'id' => $managedUser->id,
            'email' => 'managed-delete-cancel@example.test',
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