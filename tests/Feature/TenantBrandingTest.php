<?php

namespace Tests\Feature;

use App\Http\Middleware\MenuAccessMiddleware;
use App\Http\Middleware\PermissionMiddleware;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantBrandingTest extends TestCase
{
    use RefreshDatabase;

    protected function createAdminHr(): User
    {
        $this->seed(RolesTableSeeder::class);

        return User::factory()->create([
            'role_id' => Role::where('name', 'Admin HR')->firstOrFail()->id,
            'role' => 'admin_hr',
            'status' => 'active',
        ]);
    }

    public function test_tenant_form_menampilkan_input_branding_tunggal(): void
    {
        $admin = $this->createAdminHr();

        $response = $this->actingAs($admin)->get(route('tenants.create'));

        $response->assertOk();
        $response->assertSee('data-testid="tenant-branding-input"', false);
        $response->assertSee('Logo / Favicon Tenant');
    }

    public function test_tenant_bisa_menyimpan_branding_tunggal_untuk_logo_dan_favicon(): void
    {
        Storage::fake('public');

        $this->withoutMiddleware([
            PermissionMiddleware::class,
            MenuAccessMiddleware::class,
        ]);

        $admin = $this->createAdminHr();

        Tenant::query()->delete();
        $admin->forceFill(['tenant_id' => null])->save();

        $response = $this->actingAs($admin)->post(route('tenants.store'), [
            'name' => 'Tenant Branding',
            'domain' => 'tenant-branding.test',
            'status' => 'active',
            'description' => 'Tenant dengan branding upload',
            'branding' => UploadedFile::fake()->image('tenant-branding.png', 240, 80),
        ]);

        $response->assertRedirect(route('tenants.index'));

        $tenant = Tenant::query()->where('name', 'Tenant Branding')->firstOrFail();

        $this->assertNotNull($tenant->branding_path);
        $this->assertTrue(Str::startsWith($tenant->branding_path, 'storage/tenant-branding/branding/'));

        Storage::disk('public')->assertExists(Str::after($tenant->branding_path, 'storage/'));
    }

    public function test_layout_auth_menggunakan_branding_tenant_di_navbar_dan_favicon(): void
    {
        $admin = $this->createAdminHr();

        $tenant = Tenant::create([
            'name' => 'Tenant Layout Brand',
            'code' => 'TLB001',
            'slug' => 'tenant-layout-brand',
            'domain' => 'tenant-layout-brand.test',
            'status' => 'active',
            'branding_path' => 'storage/tenant-branding/branding/layout-branding.png',
        ]);

        $admin->forceFill(['tenant_id' => $tenant->id])->save();

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Tenant Layout Brand');
        $response->assertSee(asset('storage/tenant-branding/branding/layout-branding.png'), false);
        $response->assertSee('data-testid="sidebar-tenant-brand"', false);
    }

    public function test_halaman_detail_tenant_menampilkan_preview_branding_tunggal(): void
    {
        $admin = $this->createAdminHr();

        $tenant = Tenant::create([
            'name' => 'Tenant Detail Branding',
            'code' => 'TDB001',
            'slug' => 'tenant-detail-branding',
            'domain' => 'tenant-detail-branding.test',
            'status' => 'active',
            'branding_path' => 'storage/tenant-branding/branding/detail-branding.png',
        ]);

        $response = $this->actingAs($admin)->get(route('tenants.show', $tenant));

        $response->assertOk();
        $response->assertSee('data-testid="tenant-show-branding-preview"', false);
        $response->assertSee(asset('storage/tenant-branding/branding/detail-branding.png'), false);
        $response->assertSee('Logo', false);
        $response->assertSee('Favicon', false);
    }

    public function test_daftar_tenant_menampilkan_thumbnail_branding(): void
    {
        $admin = $this->createAdminHr();

        $tenant = Tenant::create([
            'name' => 'Tenant Thumbnail Branding',
            'code' => 'TTB001',
            'slug' => 'tenant-thumbnail-branding',
            'domain' => 'tenant-thumbnail-branding.test',
            'status' => 'active',
            'branding_path' => 'storage/tenant-branding/branding/thumb-branding.png',
        ]);

        $response = $this->actingAs($admin)->get(route('tenants.index'));

        $response->assertOk();
        $response->assertSee('data-testid="tenant-branding-thumb-'.$tenant->id.'"', false);
        $response->assertSee(asset('storage/tenant-branding/branding/thumb-branding.png'), false);
    }

    public function test_admin_hr_bisa_menghapus_branding_tenant(): void
    {
        Storage::fake('public');

        $this->withoutMiddleware([
            PermissionMiddleware::class,
            MenuAccessMiddleware::class,
        ]);

        $admin = $this->createAdminHr();

        Storage::disk('public')->put('tenant-branding/branding/remove-branding.png', 'dummy-content');

        $tenant = Tenant::create([
            'name' => 'Tenant Remove Branding',
            'code' => 'TRB001',
            'slug' => 'tenant-remove-branding',
            'domain' => 'tenant-remove-branding.test',
            'status' => 'active',
            'branding_path' => 'storage/tenant-branding/branding/remove-branding.png',
        ]);

        $response = $this->actingAs($admin)->delete(route('tenants.branding.destroy', $tenant));

        $response->assertRedirect(route('tenants.edit', $tenant));

        $tenant->refresh();

        $this->assertNull($tenant->branding_path);
        Storage::disk('public')->assertMissing('tenant-branding/branding/remove-branding.png');
    }

    public function test_edit_tenant_menampilkan_modal_konfirmasi_hapus_branding(): void
    {
        $admin = $this->createAdminHr();

        $tenant = Tenant::create([
            'name' => 'Tenant Modal Branding',
            'code' => 'TMB001',
            'slug' => 'tenant-modal-branding',
            'domain' => 'tenant-modal-branding.test',
            'status' => 'active',
            'branding_path' => 'storage/tenant-branding/branding/modal-branding.png',
        ]);

        $response = $this->actingAs($admin)->get(route('tenants.edit', $tenant));

        $response->assertOk();
        $response->assertSee('data-testid="btn-remove-branding"', false);
        $response->assertSee('data-testid="tenant-remove-branding-modal"', false);
        $response->assertSee('data-testid="confirm-remove-branding"', false);
    }
}