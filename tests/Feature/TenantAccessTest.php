<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_cannot_access_tenant_management_routes(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Access',
            'slug' => 'tenant-access',
            'domain' => 'tenant-access.test',
            'status' => 'active',
        ]);

        $manager = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Manager Access',
            'email' => 'manager-access@example.test',
            'password' => 'password',
            'role' => 'manager',
            'status' => 'active',
        ]);

        $this->actingAs($manager)
            ->get(route('tenants.show', $tenant))
            ->assertForbidden();

        $this->actingAs($manager)
            ->get(route('tenants.edit', $tenant))
            ->assertForbidden();

        $this->actingAs($manager)
            ->get(route('tenants.create'))
            ->assertForbidden();

        $this->actingAs($manager)
            ->post(route('tenants.store'), [
                'name' => 'Blocked Tenant',
                'domain' => 'blocked-tenant.test',
                'status' => 'active',
            ])
            ->assertForbidden();

        $this->actingAs($manager)
            ->put(route('tenants.update', $tenant), [
                'name' => 'Updated Tenant Access',
                'domain' => 'updated-tenant-access.test',
                'status' => 'active',
            ])
            ->assertForbidden();

        $this->actingAs($manager)
            ->delete(route('tenants.destroy', $tenant))
            ->assertForbidden();

        $this->actingAs($manager)
            ->delete(route('tenants.branding.destroy', $tenant))
            ->assertForbidden();
    }
}