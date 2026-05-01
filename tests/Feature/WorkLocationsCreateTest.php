<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkLocationsCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_form_tampil_dengan_field_lengkap(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Lokasi Kerja',
            'slug' => 'tenant-lokasi-kerja',
            'domain' => 'tenant-lokasi-kerja.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Lokasi Kerja',
            'email' => 'admin-lokasi-kerja@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('work_locations.create'));

        $response->assertOk();
        $response->assertSee('Tambah Lokasi Kerja Baru');
        $response->assertSee('data-testid="work-locations-create-form"', false);
        $response->assertSee('name="tenant_id"', false);
        $response->assertSee('name="name"', false);
        $response->assertSee('name="radius"', false);
        $response->assertSee('name="address"', false);
        $response->assertSee('name="latitude"', false);
        $response->assertSee('name="longitude"', false);
        $response->assertSee('Gunakan nama resmi lokasi kerja');
        $response->assertSee('Radius validasi kehadiran');
        $response->assertSee('Tulis alamat lengkap lokasi kerja');
        $response->assertSee('Contoh: -6.1754');
        $response->assertSee('Contoh: 106.8272');
        $response->assertSee('Simpan Lokasi Kerja');
        $response->assertSee('fas fa-save me-1', false);
        $response->assertSee('fas fa-times me-1', false);
        $response->assertSee($tenant->name);
    }

    public function test_validasi_tenant_nama_dan_radius_berjalan(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Validasi Lokasi',
            'slug' => 'tenant-validasi-lokasi',
            'domain' => 'tenant-validasi-lokasi.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Validasi Lokasi',
            'email' => 'admin-validasi-lokasi@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post(route('work_locations.store'), [
                'latitude' => -6.1754,
                'longitude' => 106.8272,
                'radius' => 'radius-tidak-valid',
            ])
            ->assertSessionHasErrors(['tenant_id', 'name', 'radius']);
    }

    public function test_lokasi_kerja_tersimpan_dengan_tenant_dan_flash_message(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Simpan Lokasi',
            'slug' => 'tenant-simpan-lokasi',
            'domain' => 'tenant-simpan-lokasi.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Simpan Lokasi',
            'email' => 'admin-simpan-lokasi@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('work_locations.store'), [
                'tenant_id' => $tenant->id,
                'name' => 'Kantor Pusat Jakarta',
                'radius' => 150,
                'address' => 'Jalan Medan Merdeka Selatan No. 1, Jakarta',
                'latitude' => -6.1754,
                'longitude' => 106.8272,
            ]);

        $response->assertRedirect(route('work_locations.index'));
        $response->assertSessionHas('success', 'Lokasi kerja berhasil ditambahkan');

        $this->assertDatabaseHas('work_locations', [
            'tenant_id' => $tenant->id,
            'name' => 'Kantor Pusat Jakarta',
            'radius' => 150,
            'address' => 'Jalan Medan Merdeka Selatan No. 1, Jakarta',
        ]);
    }

    public function test_empty_state_muncul_jika_tenant_kosong(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Empty Lokasi',
            'slug' => 'tenant-empty-lokasi',
            'domain' => 'tenant-empty-lokasi.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Empty Lokasi',
            'email' => 'admin-empty-lokasi@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        Tenant::query()->delete();

        $response = $this->actingAs($admin)->get(route('work_locations.create'));

        $response->assertOk();
        $response->assertSee('data-testid="work-locations-create-empty-state"', false);
        $response->assertSee('Belum ada tenant, silakan buat tenant terlebih dahulu');
        $response->assertDontSee('data-testid="work-locations-create-form"', false);
    }
}