<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Support\SystemStatusChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarSystemStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_footer_menampilkan_status_sistem_dan_badge_hijau_saat_normal(): void
    {
        $tenant = $this->makeTenant('sidebar-system-status-normal');
        $admin = $this->makeUser($tenant, 'admin-system-status-normal@example.test');

        app()->instance(SystemStatusChecker::class, new class {
            public function status(): string
            {
                return 'normal';
            }
        });

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Status Sistem');
        $response->assertSee('Normal');
        $response->assertSee('bg-gradient-success', false);
        $response->assertSee('data-testid="sidebar-system-status-footer"', false);
        $response->assertSee('data-testid="sidebar-system-status-badge"', false);
    }

    public function test_footer_menampilkan_badge_merah_saat_error_simulasi(): void
    {
        $tenant = $this->makeTenant('sidebar-system-status-error');
        $admin = $this->makeUser($tenant, 'admin-system-status-error@example.test');

        app()->instance(SystemStatusChecker::class, new class {
            public function status(): string
            {
                return 'error';
            }
        });

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Status Sistem');
        $response->assertSee('Ada Error');
        $response->assertSee('bg-gradient-danger', false);
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

    protected function makeUser(Tenant $tenant, string $email): User
    {
        return User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Sidebar System Status',
            'email' => $email,
            'password' => 'password123',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);
    }
}