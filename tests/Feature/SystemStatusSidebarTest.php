<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Support\SystemStatusChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemStatusSidebarTest extends TestCase
{
    use RefreshDatabase;

    public function test_sidebar_shows_normal_status(): void
    {
        $user = $this->makeAdminUser('system-status-sidebar-normal');

        app()->instance(SystemStatusChecker::class, new class {
            public function status(): string
            {
                return 'normal';
            }
        });

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Status Sistem:', false);
        $response->assertSee('Normal', false);
        $response->assertSee('bg-gradient-success', false);
    }

    public function test_sidebar_shows_error_status(): void
    {
        $user = $this->makeAdminUser('system-status-sidebar-error');

        app()->instance(SystemStatusChecker::class, new class {
            public function status(): string
            {
                return 'error';
            }
        });

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Status Sistem:', false);
        $response->assertSee('Ada Error', false);
        $response->assertSee('bg-gradient-danger', false);
    }

    protected function makeAdminUser(string $slug): User
    {
        $tenant = Tenant::create([
            'name' => ucfirst(str_replace('-', ' ', $slug)).' Tenant',
            'slug' => $slug,
            'domain' => $slug.'.test',
            'status' => 'active',
        ]);

        return User::create([
            'tenant_id' => $tenant->id,
            'name' => 'System Status Sidebar Admin',
            'email' => $slug.'@example.test',
            'password' => 'password123',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);
    }
}