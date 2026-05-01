<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantOwnerPanelTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);

        $ownerRole = Role::where('name', 'Owner')->firstOrFail();

        $this->owner = User::create([
            'name' => 'Global Owner',
            'email' => 'owner@example.test',
            'password' => 'password123',
            'role_id' => $ownerRole->id,
            'status' => 'active',
        ]);
    }

    public function test_owner_can_tambah_tenant_baru(): void
    {
        $response = $this->actingAs($this->owner)->post(route('owner.tenants.store'), [
            'name' => 'Tenant Baru',
            'domain' => 'tenant-baru.test',
            'status' => 'active',
            'subscription_plan' => 'pro',
            'description' => 'Tenant baru untuk panel owner',
        ]);

        $response->assertRedirect(route('owner.tenants.index'));
        $response->assertSessionHas('success', 'Tenant berhasil ditambahkan.');

        $this->assertDatabaseHas('tenants', [
            'name' => 'Tenant Baru',
            'domain' => 'tenant-baru.test',
            'status' => 'active',
            'subscription_plan' => 'pro',
            'description' => 'Tenant baru untuk panel owner',
        ]);
    }

    public function test_owner_bisa_edit_subscription_tenant(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Subscription',
            'slug' => 'tenant-subscription',
            'domain' => 'tenant-subscription.test',
            'status' => 'active',
            'subscription_plan' => 'basic',
        ]);

        $response = $this->actingAs($this->owner)->put(route('owner.tenants.update', $tenant), [
            'name' => 'Tenant Subscription',
            'domain' => 'tenant-subscription.test',
            'status' => 'active',
            'subscription_plan' => 'enterprise',
            'description' => 'Upgrade paket enterprise',
        ]);

        $response->assertRedirect(route('owner.tenants.index'));
        $response->assertSessionHas('success', 'Tenant berhasil diperbarui.');

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'subscription_plan' => 'enterprise',
            'description' => 'Upgrade paket enterprise',
        ]);
    }

    public function test_owner_tidak_bisa_hapus_tenant_yang_masih_punya_user_atau_karyawan(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Sibuk',
            'slug' => 'tenant-sibuk',
            'domain' => 'tenant-sibuk.test',
            'status' => 'active',
            'subscription_plan' => 'basic',
        ]);

        $employeeRole = Role::where('name', 'Employee')->firstOrFail();

        User::create([
            'tenant_id' => $tenant->id,
            'name' => 'User Tenant Sibuk',
            'email' => 'user-tenant-sibuk@example.test',
            'password' => 'password123',
            'role_id' => $employeeRole->id,
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'EMP-OWNER-001',
            'name' => 'Karyawan Tenant Sibuk',
            'email' => 'karyawan-tenant-sibuk@example.test',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->owner)->delete(route('owner.tenants.destroy', $tenant));

        $response->assertRedirect(route('owner.tenants.index'));
        $response->assertSessionHasErrors('tenant');
        $this->assertDatabaseHas('tenants', ['id' => $tenant->id]);
    }

    public function test_owner_bisa_lihat_summary_tenant(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Ringkasan',
            'slug' => 'tenant-ringkasan-owner',
            'domain' => 'tenant-ringkasan-owner.test',
            'status' => 'active',
            'subscription_plan' => 'pro',
        ]);

        $employeeRole = Role::where('name', 'Employee')->firstOrFail();

        User::create([
            'tenant_id' => $tenant->id,
            'name' => 'User Summary',
            'email' => 'user-summary@example.test',
            'password' => 'password123',
            'role_id' => $employeeRole->id,
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'EMP-SUM-001',
            'name' => 'Employee Summary',
            'email' => 'employee-summary@example.test',
            'status' => 'active',
        ]);

        Department::create([
            'tenant_id' => $tenant->id,
            'name' => 'Departemen Summary',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->owner)->get(route('owner.tenants.index'));

        $response->assertOk();
        $response->assertSee('Tenant Ringkasan');
        $response->assertSee('tenant-ringkasan-owner.test');
        $response->assertSee('Pro');
        $response->assertSee('data-testid="owner-tenant-users-count-'.$tenant->id.'">1', false);
        $response->assertSee('data-testid="owner-tenant-employees-count-'.$tenant->id.'">1', false);
        $response->assertSee('data-testid="owner-tenant-departments-count-'.$tenant->id.'">1', false);
    }
}