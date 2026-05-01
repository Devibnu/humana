<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Services\HealthCheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthCheckServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_connection_ok(): void
    {
        $status = app(HealthCheckService::class)->checkDatabase();

        $this->assertSame('ok', $status);
    }

    public function test_queue_connection_ok(): void
    {
        $status = app(HealthCheckService::class)->checkQueue();

        $this->assertSame('ok', $status);
    }

    public function test_cache_connection_ok(): void
    {
        $status = app(HealthCheckService::class)->checkCache();

        $this->assertSame('ok', $status);
    }

    public function test_sidebar_shows_normal_when_all_ok(): void
    {
        config(['system.health' => 'normal']);

        $response = $this->actingAs($this->makeAdminUser('health-check-service-normal'))->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Status Sistem:', false);
        $response->assertSee('Normal', false);
        $response->assertSee('bg-gradient-success', false);
    }

    public function test_sidebar_shows_error_when_any_fail(): void
    {
        config(['system.health' => 'error']);

        $response = $this->actingAs($this->makeAdminUser('health-check-service-error'))->get('/dashboard');

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
            'name' => 'Health Check Service Admin',
            'email' => $slug.'@example.test',
            'password' => 'password123',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);
    }
}