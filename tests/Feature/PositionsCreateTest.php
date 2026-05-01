<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PositionsCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_form_tampil_dengan_field_lengkap(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Posisi Create',
            'code' => 'TPC-01',
            'slug' => 'tenant-posisi-create',
            'domain' => 'tenant-posisi-create.test',
            'status' => 'active',
        ]);

        $department = Department::create([
            'tenant_id' => $tenant->id,
            'name' => 'Operasional',
            'code' => 'OPS',
            'description' => 'Tim operasional inti.',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Posisi Create',
            'email' => 'admin-posisi-create@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('positions.create'));

        $response->assertOk();
        $response->assertSee('Tambah Posisi Baru');
        $response->assertSee('data-testid="positions-create-form"', false);
        $response->assertSee('name="name"', false);
        $response->assertSee('name="code"', false);
        $response->assertSee('name="description"', false);
        $response->assertSee('name="tenant_id"', false);
        $response->assertSee('name="department_id"', false);
        $response->assertSee('name="status"', false);
        $response->assertSee('Gunakan nama resmi sesuai struktur organisasi');
        $response->assertSee('Contoh: MGR-01');
        $response->assertSee('Tuliskan deskripsi singkat posisi');
        $response->assertSee('Hanya departemen yang sesuai tenant terpilih yang dapat digunakan.');
        $response->assertSee('Aktif');
        $response->assertSee('Non-Aktif');
        $response->assertSee('Simpan Posisi');
        $response->assertSee('fas fa-save me-1', false);
        $response->assertSee('fas fa-times me-1', false);
        $response->assertSee($tenant->name);
        $response->assertSee($department->name);
    }

    public function test_validasi_nama_dan_departemen_wajib(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Posisi Validasi',
            'code' => 'TPV-01',
            'slug' => 'tenant-posisi-validasi',
            'domain' => 'tenant-posisi-validasi.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Posisi Validasi',
            'email' => 'admin-posisi-validasi@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post(route('positions.store'), [
                'tenant_id' => $tenant->id,
                'status' => 'active',
            ])
            ->assertSessionHasErrors(['name', 'department_id']);
    }

    public function test_posisi_tersimpan_dengan_relasi_departemen(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Posisi Simpan',
            'code' => 'TPS-01',
            'slug' => 'tenant-posisi-simpan',
            'domain' => 'tenant-posisi-simpan.test',
            'status' => 'active',
        ]);

        $department = Department::create([
            'tenant_id' => $tenant->id,
            'name' => 'Keuangan',
            'code' => 'FIN',
            'description' => 'Tim keuangan perusahaan.',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Posisi Simpan',
            'email' => 'admin-posisi-simpan@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('positions.store'), [
                'tenant_id' => $tenant->id,
                'department_id' => $department->id,
                'name' => 'Manajer Keuangan',
                'code' => 'MGR-FIN-01',
                'description' => 'Memimpin perencanaan dan kontrol keuangan.',
                'status' => 'active',
            ]);

        $response->assertRedirect(route('positions.index'));
        $response->assertSessionHas('success', 'Posisi berhasil ditambahkan');

        $this->assertDatabaseHas('positions', [
            'tenant_id' => $tenant->id,
            'department_id' => $department->id,
            'name' => 'Manajer Keuangan',
            'code' => 'MGR-FIN-01',
            'description' => 'Memimpin perencanaan dan kontrol keuangan.',
            'status' => 'active',
        ]);
    }

    public function test_empty_state_muncul_jika_tenant_atau_departemen_belum_ada(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Posisi Empty',
            'code' => 'TPE-01',
            'slug' => 'tenant-posisi-empty',
            'domain' => 'tenant-posisi-empty.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Posisi Empty',
            'email' => 'admin-posisi-empty@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('positions.create'));

        $response->assertOk();
        $response->assertSee('data-testid="positions-create-empty-state"', false);
        $response->assertSee('Belum ada tenant/departemen, silakan buat terlebih dahulu');
        $response->assertDontSee('data-testid="positions-create-form"', false);
    }
}