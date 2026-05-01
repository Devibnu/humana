<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProfileFlashMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_success_flash_message_is_visible_on_users_index_after_delete_redirect(): void
    {
        [$admin] = $this->createAdminAndManagedUser('detail-flash-success');

        $this->actingAs($admin)
            ->withSession(['success' => 'User berhasil dihapus.'])
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('fa-check-circle', false)
            ->assertSee('User berhasil dihapus.');
    }

    public function test_error_flash_message_is_visible_on_detail_page_after_failed_delete_redirect(): void
    {
        [$admin, $managedUser] = $this->createAdminAndManagedUser('detail-flash-error');

        User::deleting(function (User $user) use ($managedUser) {
            if ($user->is($managedUser)) {
                throw new \RuntimeException('Forced delete failure for test.');
            }
        });

        $this->actingAs($admin)
            ->delete(route('users.show-profile.destroy', $managedUser))
            ->assertRedirect(route('users.show-profile', $managedUser))
            ->assertSessionHas('error', 'User gagal dihapus, silakan coba lagi.');

        $this->actingAs($admin)
            ->withSession(['error' => 'User gagal dihapus, silakan coba lagi.'])
            ->get(route('users.show-profile', $managedUser))
            ->assertOk()
            ->assertSee('fa-exclamation-circle', false)
            ->assertSee('User gagal dihapus, silakan coba lagi.');
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
            'name' => 'Admin Flash Detail',
            'email' => 'admin-'.$slug.'@example.test',
            'password' => 'password123',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $managedUser = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Managed Flash Detail',
            'email' => 'managed-'.$slug.'@example.test',
            'password' => 'password123',
            'role' => 'employee',
            'status' => 'active',
        ]);

        return [$admin, $managedUser];
    }
}