<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantsIndexUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_hr_melihat_icon_view_edit_delete_di_kolom_action(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant UI',
            'code' => 'TNT-UI',
            'slug' => 'tenant-ui',
            'domain' => 'tenant-ui.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Tenant UI',
            'email' => 'admin-tenant-ui@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('tenants.index'));

        $response->assertOk();
        $response->assertSee(route('tenants.show', $tenant), false);
        $response->assertSee(route('tenants.edit', $tenant), false);
        $response->assertSee(route('tenants.destroy', $tenant), false);
        $response->assertSee('fas fa-eye text-info', false);
        $response->assertSee('fas fa-edit text-secondary', false);
        $response->assertSee('fas fa-trash text-danger', false);
        $response->assertSee('data-bs-target="#deleteTenantModal'.$tenant->id.'"', false);
        $response->assertSee('id="deleteTenantModal'.$tenant->id.'"', false);
        $response->assertSee('data-testid="tenant-index-delete-modal-'.$tenant->id.'"', false);
        $response->assertSee('Konfirmasi Hapus Tenant');
        $response->assertSee('Hapus');
    }

    public function test_hanya_admin_hr_bisa_delete_tenant(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Delete Scope',
            'code' => 'TNT-DEL',
            'slug' => 'tenant-delete-scope',
            'domain' => 'tenant-delete-scope.test',
            'status' => 'active',
        ]);

        $manager = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Manager Tenant UI',
            'email' => 'manager-tenant-ui@example.test',
            'password' => 'password',
            'role' => 'manager',
            'status' => 'active',
        ]);

        $this->actingAs($manager)
            ->delete(route('tenants.destroy', $tenant))
            ->assertForbidden();
    }
}