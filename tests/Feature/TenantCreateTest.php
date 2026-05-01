<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_hr_can_create_tenant(): void
    {
        $this->seed();

        // Seeder menyediakan default tenant, hapus untuk uji skenario create pertama.
        Tenant::query()->delete();

        $loginResponse = $this->post(route('session.store'), [
            'email' => 'admin@humana.test',
            'password' => 'password',
        ]);

        $loginResponse->assertRedirect(route('dashboard'));

        $tenantData = [
            'name' => 'PT Contoh Tenant',
            'domain' => 'contoh-tenant.test',
            'status' => 'active',
            'description' => 'Tenant contoh untuk uji regression',
        ];

        $response = $this->post(route('tenants.store'), $tenantData);

        $response->assertRedirect(route('tenants.index'));

        $this->assertDatabaseHas('tenants', [
            'name' => 'PT Contoh Tenant',
            'domain' => 'contoh-tenant.test',
            'status' => 'active',
            'description' => 'Tenant contoh untuk uji regression',
        ]);

        $this->assertNotNull(Tenant::where('domain', 'contoh-tenant.test')->value('code'));
    }

    public function test_admin_hr_cannot_create_second_tenant_when_one_already_exists(): void
    {
        $this->seed();

        $existingTenant = Tenant::query()->firstOrFail();

        $admin = User::where('email', 'admin@humana.test')->firstOrFail();

        $response = $this->actingAs($admin)->post(route('tenants.store'), [
            'name' => 'Tenant Kedua',
            'domain' => 'tenant-kedua.test',
            'status' => 'active',
            'description' => 'Tidak boleh tersimpan',
        ]);

        $response->assertRedirect(route('tenants.index'));
        $response->assertSessionHasErrors([
            'tenant' => 'Hanya satu tenant yang diperbolehkan.',
        ]);

        $this->assertSame(1, Tenant::count());
        $this->assertDatabaseHas('tenants', [
            'id' => $existingTenant->id,
            'domain' => $existingTenant->domain,
        ]);
        $this->assertDatabaseMissing('tenants', [
            'domain' => 'tenant-kedua.test',
        ]);
    }

    public function test_tenant_success_flash_is_only_rendered_once_in_main_content(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@humana.test')->firstOrFail();

        $response = $this->actingAs($admin)
            ->withSession(['success' => 'Tenant berhasil ditambahkan.'])
            ->get(route('tenants.index'));

        $response->assertOk();
        $response->assertSee('Tenant berhasil ditambahkan.');
        $this->assertSame(1, substr_count($response->getContent(), 'Tenant berhasil ditambahkan.'));
    }

    public function test_edit_tenant_form_tetap_mengirim_domain_existing(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@humana.test')->firstOrFail();

        $tenant = Tenant::create([
            'name' => 'Tenant Edit Form Regression',
            'code' => 'TEF001',
            'slug' => 'tenant-edit-form-regression',
            'domain' => 'tenant-edit-form-regression.test',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('tenants.edit', $tenant));

        $response->assertOk();
        $response->assertSee('data-testid="tenants-edit-card"', false);
        $response->assertSee('data-testid="tenants-edit-form"', false);
        $response->assertSee('name="domain"', false);
        $response->assertSee('value="tenant-edit-form-regression.test"', false);
        $response->assertSee('Domain tenant dipertahankan saat edit', false);
    }
}