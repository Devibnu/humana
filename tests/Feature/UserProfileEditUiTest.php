<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserProfileEditUiTest extends TestCase
{
    use RefreshDatabase;

    // ─────────────────────────────────────────
    //  Layout assertions
    // ─────────────────────────────────────────

    public function test_edit_user_page_is_full_wide_layout(): void
    {
        [$admin, $target] = $this->makeAdminAndTarget('ui-fullwide');

        $response = $this->actingAs($admin)->get(route('users.profile-edit', $target));

        $response->assertOk();
        $response->assertSee('col-12', false);
        $response->assertSee('card mx-4 mb-4', false);
    }

    public function test_edit_user_page_has_two_inner_cards(): void
    {
        [$admin, $target] = $this->makeAdminAndTarget('ui-two-cols');

        $response = $this->actingAs($admin)->get(route('users.profile-edit', $target));

        $response->assertOk();
        $response->assertSee('col-lg-7', false);
        $response->assertSee('col-lg-5', false);
        $response->assertSee('Account Information');
        $response->assertSee('Role & Account', false);
    }

    public function test_edit_user_header_shows_role_badge(): void
    {
        [$admin, $target] = $this->makeAdminAndTarget('ui-badge');

        $response = $this->actingAs($admin)->get(route('users.profile-edit', $target));

        $response->assertOk();
        $response->assertSee($target->name);
        $response->assertSee('Employee');
    }

    public function test_edit_user_form_has_all_required_fields(): void
    {
        [$admin, $target] = $this->makeAdminAndTarget('ui-fields');

        $response = $this->actingAs($admin)->get(route('users.profile-edit', $target));

        $response->assertOk();
        $response->assertSee('name="name"', false);
        $response->assertSee('name="email"', false);
        $response->assertSee('name="password"', false);
        $response->assertSee('name="password_confirmation"', false);
        $response->assertSee('name="avatar"', false);
        $response->assertSee('name="tenant_id"', false);
        $response->assertSee('name="role_id"', false);
        $response->assertSee('name="status"', false);
    }

    public function test_edit_user_buttons_are_right_aligned_with_save_icon(): void
    {
        [$admin, $target] = $this->makeAdminAndTarget('ui-buttons');

        $response = $this->actingAs($admin)->get(route('users.profile-edit', $target));

        $response->assertOk();
        $response->assertSee('justify-content-end', false);
        $response->assertSee('fa-save', false);
        $response->assertSee('Update User');
        $response->assertSee('Cancel');
    }

    public function test_status_rendered_as_radio_buttons(): void
    {
        [$admin, $target] = $this->makeAdminAndTarget('ui-status-radio');

        $response = $this->actingAs($admin)->get(route('users.profile-edit', $target));

        $response->assertOk();
        $response->assertSee('type="radio"', false);
        $response->assertSee('name="status"', false);
    }

    // ─────────────────────────────────────────
    //  Flash message after update
    // ─────────────────────────────────────────

    public function test_success_flash_set_after_admin_updates_user(): void
    {
        [$admin, $target] = $this->makeAdminAndTarget('ui-flash-update');

        $this->actingAs($admin)
            ->put(route('users.update', $target), [
                'tenant_id' => $target->tenant_id,
                'name'      => 'Updated Name',
                'email'     => $target->email,
                'role'      => 'employee',
                'status'    => 'active',
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success', 'User berhasil diperbarui.');
    }

    public function test_flash_message_shown_in_users_index_after_update(): void
    {
        [$admin] = $this->makeAdminAndTarget('ui-flash-index');

        $this->actingAs($admin)
            ->withSession(['success' => 'User berhasil diperbarui.'])
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('fa-check-circle', false)
            ->assertSee('User berhasil diperbarui.');
    }

    public function test_update_success_flash_is_only_rendered_once_in_users_index(): void
    {
        [$admin] = $this->makeAdminAndTarget('ui-flash-once');

        $response = $this->actingAs($admin)
            ->withSession(['success' => 'User berhasil diperbarui.'])
            ->get(route('users.index'));

        $response->assertOk();
        $response->assertSee('User berhasil diperbarui.');
        $this->assertSame(1, substr_count($response->getContent(), 'User berhasil diperbarui.'));
    }

    public function test_validation_errors_shown_on_edit_page_after_invalid_submit(): void
    {
        [$admin, $target] = $this->makeAdminAndTarget('ui-flash-val');

        $this->actingAs($admin)
            ->put(route('users.update', $target), [
                'tenant_id' => $target->tenant_id,
                'name'      => '',
                'email'     => 'not-an-email',
                'role'      => 'employee',
                'status'    => 'active',
            ])
            ->assertSessionHasErrors(['name', 'email']);
    }

    // ─────────────────────────────────────────
    //  Avatar upload via admin edit
    // ─────────────────────────────────────────

    public function test_admin_can_upload_avatar_when_updating_user(): void
    {
        Storage::fake('public');

        [$admin, $target] = $this->makeAdminAndTarget('ui-avatar-upload');

        $avatar = UploadedFile::fake()->image('user-avatar.png');

        $this->actingAs($admin)
            ->put(route('users.update', $target), [
                'tenant_id' => $target->tenant_id,
                'name'      => $target->name,
                'email'     => $target->email,
                'role'      => 'employee',
                'status'    => 'active',
                'avatar'    => $avatar,
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success');

        $target->refresh();
        $this->assertNotNull($target->avatar_path);
        Storage::disk('public')->assertExists($target->avatar_path);
    }

    public function test_admin_can_remove_avatar_when_updating_user(): void
    {
        Storage::fake('public');

        [$admin, $target] = $this->makeAdminAndTarget('ui-avatar-remove');

        // Give target an avatar first
        $path = UploadedFile::fake()->image('existing.png')->store('avatars', 'public');
        $target->update(['avatar_path' => $path]);
        Storage::disk('public')->assertExists($path);

        $this->actingAs($admin)
            ->put(route('users.update', $target), [
                'tenant_id'    => $target->tenant_id,
                'name'         => $target->name,
                'email'        => $target->email,
                'role'         => 'employee',
                'status'       => 'active',
                'remove_avatar' => '1',
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success');

        $target->refresh();
        $this->assertNull($target->avatar_path);
        Storage::disk('public')->assertMissing($path);
    }

    // ─────────────────────────────────────────
    //  Password confirmation
    // ─────────────────────────────────────────

    public function test_admin_can_update_user_password_with_confirmation(): void
    {
        [$admin, $target] = $this->makeAdminAndTarget('ui-pwd-confirm');

        $this->actingAs($admin)
            ->put(route('users.update', $target), [
                'tenant_id'             => $target->tenant_id,
                'name'                  => $target->name,
                'email'                 => $target->email,
                'role'                  => 'employee',
                'status'                => 'active',
                'password'              => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success');
    }

    public function test_password_update_fails_when_confirmation_mismatches(): void
    {
        [$admin, $target] = $this->makeAdminAndTarget('ui-pwd-mismatch');

        $this->actingAs($admin)
            ->put(route('users.update', $target), [
                'tenant_id'             => $target->tenant_id,
                'name'                  => $target->name,
                'email'                 => $target->email,
                'role'                  => 'employee',
                'status'                => 'active',
                'password'              => 'newpassword123',
                'password_confirmation' => 'different456',
            ])
            ->assertSessionHasErrors('password');
    }

    // ─────────────────────────────────────────
    //  RBAC
    // ─────────────────────────────────────────

    public function test_manager_cannot_access_edit_user_page(): void
    {
        $tenant  = $this->makeTenant('ui-rbac-mgr');
        $manager = $this->makeUser('manager', $tenant, 'mgr-edit-ui@example.test');
        $target  = $this->makeUser('employee', $tenant, 'target-edit-ui@example.test');

        $this->actingAs($manager)
            ->get(route('users.profile-edit', $target))
            ->assertForbidden();
    }

    // ─────────────────────────────────────────
    //  Helpers
    // ─────────────────────────────────────────

    protected function makeAdminAndTarget(string $slug): array
    {
        $tenant = $this->makeTenant($slug);
        $admin  = $this->makeUser('admin_hr', $tenant, "admin-{$slug}@example.test");
        $target = $this->makeUser('employee', $tenant, "target-{$slug}@example.test");

        return [$admin, $target];
    }

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
            'name'      => 'Edit UI Test User',
            'email'     => $email,
            'password'  => 'password123',
            'role'      => $role,
            'status'    => 'active',
        ]);
    }
}
