<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Support\SystemStatusChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarSystemStatusRelocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_sistem_tidak_muncul_di_snapshot_dashboard_dan_tampil_di_footer_sidebar_saat_normal(): void
    {
        $tenant = $this->makeTenant('sidebar-system-status-relocation-normal');
        $admin = $this->makeUser($tenant, 'admin-system-status-relocation-normal@example.test');

        app()->instance(SystemStatusChecker::class, new class {
            public function status(): string
            {
                return 'normal';
            }
        });

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Ringkasan Dashboard HR');
        $response->assertDontSee('STATUS SISTEM');
        $response->assertSee('data-testid="sidebar-system-status-footer"', false);
        $response->assertSee('data-testid="sidebar-system-status-icon"', false);
        $response->assertSee('Status Sistem');
        $response->assertSee('Normal');
        $response->assertSee('bg-gradient-success', false);
    }

    public function test_status_sistem_tetap_di_footer_sidebar_dengan_badge_merah_saat_error(): void
    {
        $tenant = $this->makeTenant('sidebar-system-status-relocation-error');
        $admin = $this->makeUser($tenant, 'admin-system-status-relocation-error@example.test');

        app()->instance(SystemStatusChecker::class, new class {
            public function status(): string
            {
                return 'error';
            }
        });

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('STATUS SISTEM');
        $response->assertSee('data-testid="sidebar-system-status-footer"', false);
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
            'name' => 'Admin Sidebar System Status Relocation',
            'email' => $email,
            'password' => 'password123',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);
    }
}