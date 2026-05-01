<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsersFilterBadgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_index_active_filter_badge_shows_tenant_name(): void
    {
        $tenant = Tenant::create([
            'name' => 'Users Label Tenant',
            'slug' => 'users-label-tenant',
            'domain' => 'users-label-tenant.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Users Label Admin',
            'email' => 'users-label-admin@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('users.index', [
            'tenant_id' => $tenant->id,
            'role' => 'employee',
            'linked' => 'only',
        ]));

        $response->assertOk();
        $response->assertSee('Active filters');
        $response->assertSee('Tenant: Users Label Tenant');
        $response->assertSee('Role: Employee');
        $response->assertSee('Linked: Only');
    }
}