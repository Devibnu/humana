<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProfileDeleteModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_delete_button_opens_bootstrap_modal_markup_on_detail_page(): void
    {
        [$admin, $managedUser] = $this->createAdminAndManagedUser('detail-delete-modal');

        $response = $this->actingAs($admin)->get(route('users.show-profile', $managedUser));

        $response->assertOk();
        $response->assertSee('data-bs-toggle="modal"', false);
        $response->assertSee('data-bs-target="#deleteUserDetailModal-'.$managedUser->id.'"', false);
        $response->assertSee('id="deleteUserDetailModal-'.$managedUser->id.'"', false);
        $response->assertSee('Konfirmasi Hapus User');
        $response->assertSee('Confirm Delete');
    }

    public function test_delete_modal_contains_cancel_button_that_dismisses_modal(): void
    {
        [$admin, $managedUser] = $this->createAdminAndManagedUser('detail-delete-cancel');

        $response = $this->actingAs($admin)->get(route('users.show-profile', $managedUser));

        $response->assertOk();
        $response->assertSee('data-bs-dismiss="modal"', false);
        $response->assertSee('Cancel');
        $response->assertSee(route('users.show-profile.destroy', $managedUser), false);
    }

    public function test_confirm_delete_removes_user_and_redirects_to_index(): void
    {
        [$admin, $managedUser] = $this->createAdminAndManagedUser('detail-delete-confirm');

        $this->actingAs($admin)
            ->delete(route('users.show-profile.destroy', $managedUser))
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success', 'User berhasil dihapus.');

        $this->assertDatabaseMissing('users', [
            'id' => $managedUser->id,
        ]);
    }

    protected function createAdminAndManagedUser(string $slug): array
    {
        $tenant = Tenant::create([
            'name' => ucfirst(str_replace('-', ' ', $slug)).' Tenant',
            'slug' => $slug,
            'domain' => $slug.'.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Detail Delete',
            'email' => 'admin-'.$slug.'@example.test',
            'password' => 'password123',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $managedUser = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Managed Detail Delete',
            'email' => 'managed-'.$slug.'@example.test',
            'password' => 'password123',
            'role' => 'employee',
            'status' => 'active',
        ]);

        return [$admin, $managedUser];
    }
}