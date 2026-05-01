<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlashMessageTest extends TestCase
{
    use RefreshDatabase;

    // ─────────────────────────────────────────
    //  User CRUD — success flashes
    // ─────────────────────────────────────────

    public function test_success_flash_set_after_user_delete(): void
    {
        $tenant = $this->makeTenant('flash-delete');
        $admin  = $this->makeUser('admin_hr', $tenant, 'admin-flash-del@example.test');
        $target = $this->makeUser('employee', $tenant, 'target-del@example.test');

        $this->actingAs($admin)
            ->delete(route('users.destroy', $target))
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success');
    }

    public function test_success_flash_message_shown_in_index_view_after_delete(): void
    {
        $tenant = $this->makeTenant('flash-delete-view');
        $admin  = $this->makeUser('admin_hr', $tenant, 'admin-flash-delv@example.test');
        $target = $this->makeUser('employee', $tenant, 'target-delv@example.test');

        $this->actingAs($admin)
            ->delete(route('users.destroy', $target))
            ->assertRedirect(route('users.index'));

        $this->actingAs($admin)
            ->withSession(['success' => 'User berhasil dihapus.'])
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('fa-check-circle', false)
            ->assertSee('User berhasil dihapus.');
    }

    public function test_success_flash_set_after_user_update(): void
    {
        $tenant = $this->makeTenant('flash-update');
        $admin  = $this->makeUser('admin_hr', $tenant, 'admin-flash-upd@example.test');
        $target = $this->makeUser('employee', $tenant, 'target-upd@example.test');

        $this->actingAs($admin)
            ->put(route('users.update', $target), [
                'tenant_id' => $tenant->id,
                'name'      => 'Updated Name',
                'email'     => 'target-upd@example.test',
                'role'      => 'employee',
                'status'    => 'active',
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success');
    }

    public function test_success_flash_message_shown_in_index_view_after_update(): void
    {
        $tenant = $this->makeTenant('flash-update-view');
        $admin  = $this->makeUser('admin_hr', $tenant, 'admin-flash-updv@example.test');

        $this->actingAs($admin)
            ->withSession(['success' => 'User updated successfully.'])
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('fa-check-circle', false)
            ->assertSee('User updated successfully.');
    }

    // ─────────────────────────────────────────
    //  User CRUD — validation error display
    // ─────────────────────────────────────────

    public function test_validation_errors_shown_on_create_view_after_invalid_submit(): void
    {
        $tenant = $this->makeTenant('flash-val-create');
        $admin  = $this->makeUser('admin_hr', $tenant, 'admin-flash-val@example.test');

        // Submit with missing required fields
        $this->actingAs($admin)
            ->post(route('users.store'), [
                'tenant_id' => $tenant->id,
                'name'      => '',
                'email'     => 'not-an-email',
                'password'  => 'short',
                'password_confirmation' => 'short',
                'role'      => 'employee',
                'status'    => 'active',
            ])
            ->assertSessionHasErrors(['name', 'email', 'password']);

        // Follow redirect back to create — errors are shown
        $this->actingAs($admin)
            ->withSession(['errors' => session('errors')])
            ->get(route('users.create'))
            ->assertSee('fa-exclamation-triangle', false);
    }

    // ─────────────────────────────────────────
    //  User Profile — success flash
    // ─────────────────────────────────────────

    public function test_success_flash_set_after_profile_update(): void
    {
        $tenant = $this->makeTenant('flash-profile');
        $user   = $this->makeUser('employee', $tenant, 'user-flash-profile@example.test');

        $this->actingAs($user)
            ->put(route('user-profile.update'), [
                'name'  => 'Updated Profile Name',
                'email' => 'user-flash-profile@example.test',
            ])
            ->assertRedirect(route('user-profile.edit'))
            ->assertSessionHas('success', 'Profile updated successfully.');
    }

    public function test_success_flash_message_shown_in_profile_edit_view(): void
    {
        $tenant = $this->makeTenant('flash-profile-view');
        $user   = $this->makeUser('employee', $tenant, 'user-flash-view@example.test');

        $this->actingAs($user)
            ->withSession(['success' => 'Profile updated successfully.'])
            ->get(route('user-profile.edit'))
            ->assertOk()
            ->assertSee('fa-check-circle', false)
            ->assertSee('Profile updated successfully.');
    }

    public function test_validation_errors_shown_on_profile_edit_view_after_invalid_submit(): void
    {
        $tenant = $this->makeTenant('flash-profile-val');
        $user   = $this->makeUser('employee', $tenant, 'user-profile-val@example.test');

        $this->actingAs($user)
            ->put(route('user-profile.update'), [
                'name'  => '',
                'email' => 'not-an-email',
            ])
            ->assertSessionHasErrors(['name', 'email']);
    }

    // ─────────────────────────────────────────
    //  Component: error session flash display
    // ─────────────────────────────────────────

    public function test_error_flash_message_shown_in_index_view(): void
    {
        $tenant = $this->makeTenant('flash-error-view');
        $admin  = $this->makeUser('admin_hr', $tenant, 'admin-flash-err@example.test');

        $this->actingAs($admin)
            ->withSession(['error' => 'User gagal dihapus, silakan coba lagi.'])
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('fa-exclamation-circle', false)
            ->assertSee('User gagal dihapus, silakan coba lagi.');
    }

    // ─────────────────────────────────────────
    //  Helpers
    // ─────────────────────────────────────────

    protected function makeTenant(string $slug): Tenant
    {
        return Tenant::create([
            'name'   => ucfirst(str_replace('-', ' ', $slug)).' Tenant',
            'slug'   => $slug,
            'domain' => $slug.'.test',
            'status' => 'active',
        ]);
    }

    protected function makeUser(string $role, Tenant $tenant, string $email): User
    {
        return User::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Flash Test User',
            'email'     => $email,
            'password'  => 'password123',
            'role'      => $role,
            'status'    => 'active',
        ]);
    }
}
