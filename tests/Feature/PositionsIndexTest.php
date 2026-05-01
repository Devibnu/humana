<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Position;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PositionsIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_tabel_tampil_dengan_data_posisi(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Index Posisi',
            'code' => 'TIP-01',
            'slug' => 'tenant-index-posisi',
            'domain' => 'tenant-index-posisi.test',
            'status' => 'active',
        ]);

        $department = Department::create([
            'tenant_id' => $tenant->id,
            'name' => 'Operasional',
            'code' => 'OPS',
            'description' => 'Divisi operasional utama.',
            'status' => 'active',
        ]);

        $position = Position::create([
            'tenant_id' => $tenant->id,
            'department_id' => $department->id,
            'name' => 'Supervisor Operasional',
            'code' => 'OPS-01',
            'description' => 'Memimpin operasional harian.',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Index Posisi',
            'email' => 'admin-index-posisi@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('positions.index'));

        $response->assertOk();
        $response->assertSee('Daftar Posisi');
        $response->assertSee('data-testid="positions-filter-form"', false);
        $response->assertSee('data-testid="positions-search-input"', false);
        $response->assertSee('data-testid="positions-tenant-filter"', false);
        $response->assertSee('data-testid="positions-status-filter"', false);
        $response->assertDontSee('data-testid="positions-sort-by"', false);
        $response->assertDontSee('data-testid="positions-sort-direction"', false);
        $response->assertSee('data-testid="positions-table"', false);
        $response->assertSee('Nama Posisi');
        $response->assertSee('Kode');
        $response->assertSee('Departemen');
        $response->assertSee('Deskripsi');
        $response->assertSee('Action');
        $response->assertSee('Supervisor Operasional');
        $response->assertSee('OPS-01');
        $response->assertSee('Operasional');
        $response->assertSee('Memimpin operasional harian.');
        $response->assertSee('data-testid="position-status-'.$position->id.'"', false);
        $response->assertSee('Aktif');
        $response->assertDontSee('data-testid="position-employees-count-'.$position->id.'"', false);
        $response->assertDontSee('Jumlah Karyawan');
    }

    public function test_badge_summary_sesuai_data(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Ringkasan Posisi',
            'code' => 'TRP-01',
            'slug' => 'tenant-ringkasan-posisi',
            'domain' => 'tenant-ringkasan-posisi.test',
            'status' => 'active',
        ]);

        $department = Department::create([
            'tenant_id' => $tenant->id,
            'name' => 'Keuangan',
            'code' => 'FIN',
            'status' => 'active',
        ]);

        Position::create([
            'tenant_id' => $tenant->id,
            'department_id' => $department->id,
            'name' => 'Manager Keuangan',
            'code' => 'FIN-01',
            'status' => 'active',
        ]);

        Position::create([
            'tenant_id' => $tenant->id,
            'department_id' => $department->id,
            'name' => 'Staf Keuangan',
            'code' => 'FIN-02',
            'status' => 'inactive',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Ringkasan Posisi',
            'email' => 'admin-ringkasan-posisi@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('positions.index'));

        $response->assertOk();
        $response->assertSee('data-testid="positions-summary-total"', false);
        $response->assertSee('data-testid="positions-summary-active"', false);
        $response->assertSee('data-testid="positions-summary-inactive"', false);
        $response->assertSee('Total Posisi');
        $response->assertSee('Posisi Aktif');
        $response->assertSee('Posisi Nonaktif');
        $response->assertSee('>2<', false);
        $response->assertSee('>1<', false);
    }

    public function test_filter_posisi_menyaring_data_dan_menampilkan_badge_filter_aktif(): void
    {
        $tenantA = Tenant::create([
            'name' => 'Tenant Alpha',
            'code' => 'TA-01',
            'slug' => 'tenant-alpha',
            'domain' => 'tenant-alpha.test',
            'status' => 'active',
        ]);

        $tenantB = Tenant::create([
            'name' => 'Tenant Beta',
            'code' => 'TB-01',
            'slug' => 'tenant-beta',
            'domain' => 'tenant-beta.test',
            'status' => 'active',
        ]);

        $departmentA = Department::create([
            'tenant_id' => $tenantA->id,
            'name' => 'Operasional Alpha',
            'code' => 'OPA',
            'status' => 'active',
        ]);

        $departmentB = Department::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Operasional Beta',
            'code' => 'OPB',
            'status' => 'active',
        ]);

        Position::create([
            'tenant_id' => $tenantA->id,
            'department_id' => $departmentA->id,
            'name' => 'Supervisor Alpha',
            'code' => 'ALP-01',
            'description' => 'Posisi tenant alpha.',
            'status' => 'active',
        ]);

        Position::create([
            'tenant_id' => $tenantB->id,
            'department_id' => $departmentB->id,
            'name' => 'Supervisor Beta',
            'code' => 'BET-01',
            'description' => 'Posisi tenant beta.',
            'status' => 'inactive',
        ]);

        $admin = User::create([
            'tenant_id' => $tenantA->id,
            'name' => 'Admin Filter Posisi',
            'email' => 'admin-filter-posisi@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('positions.index', [
            'search' => 'Beta',
            'tenant_id' => $tenantB->id,
            'status' => 'inactive',
            'sort_by' => 'name',
            'sort_direction' => 'asc',
        ]));

        $response->assertOk();
        $response->assertSee('Filter aktif');
        $response->assertSee('Pencarian: Beta');
        $response->assertSee('Tenant: Tenant Beta');
        $response->assertSee('Status: Non-Aktif');
        $response->assertDontSee('Urutkan: Nama Posisi (ASC)');
        $response->assertSee('Supervisor Beta');
        $response->assertDontSee('Supervisor Alpha');
    }

    public function test_ikon_aksi_muncul_dan_berfungsi(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Aksi Posisi',
            'code' => 'TAP-01',
            'slug' => 'tenant-aksi-posisi',
            'domain' => 'tenant-aksi-posisi.test',
            'status' => 'active',
        ]);

        $department = Department::create([
            'tenant_id' => $tenant->id,
            'name' => 'Human Capital',
            'code' => 'HC',
            'status' => 'active',
        ]);

        $position = Position::create([
            'tenant_id' => $tenant->id,
            'department_id' => $department->id,
            'name' => 'HR Officer',
            'code' => 'HC-01',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Aksi Posisi',
            'email' => 'admin-aksi-posisi@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('positions.index'));

        $response->assertOk();
        $response->assertSee('data-testid="btn-open-add-position-modal"', false);
        $response->assertSee('data-testid="btn-export-positions-xlsx"', false);
        $response->assertSee('data-testid="btn-view-position-'.$position->id.'"', false);
        $response->assertSee('data-testid="btn-edit-position-'.$position->id.'"', false);
        $response->assertSee('data-testid="btn-delete-position-'.$position->id.'"', false);
        $response->assertSee('data-testid="position-index-delete-modal-'.$position->id.'"', false);
        $response->assertSee('data-testid="confirm-delete-position-'.$position->id.'"', false);
        $response->assertSee('Export Excel');
        $response->assertSee('title="Lihat"', false);
        $response->assertSee('title="Edit"', false);
        $response->assertSee('title="Hapus"', false);
        $response->assertSee('Konfirmasi Hapus Posisi');
        $response->assertSee('Apakah Anda yakin ingin menghapus posisi');
        $response->assertSee('fas fa-eye text-info', false);
        $response->assertSee('fas fa-edit text-secondary', false);
        $response->assertSee('fas fa-trash text-danger', false);
        $response->assertDontSee("confirm('Hapus posisi ini?')", false);
        $response->assertSee(route('positions.show', $position), false);
        $response->assertSee(route('positions.edit', $position), false);
    }

    public function test_modal_tambah_posisi_dirender_dengan_state_kosong_default(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Modal Kosong Posisi',
            'code' => 'TMKP-01',
            'slug' => 'tenant-modal-kosong-posisi',
            'domain' => 'tenant-modal-kosong-posisi.test',
            'status' => 'active',
        ]);

        $department = Department::create([
            'tenant_id' => $tenant->id,
            'name' => 'Information Technology',
            'code' => 'IT',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Modal Kosong Posisi',
            'email' => 'admin-modal-kosong-posisi@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('positions.index'));

        $response->assertOk();
        $response->assertSee('data-testid="positions-index-create-form"', false);
        $response->assertSee('autocomplete="off"', false);
        $response->assertSee('name="name" class="form-control" value=""', false);
        $response->assertSee('name="code" class="form-control" value=""', false);
        $response->assertSee('textarea name="description"', false);
        $response->assertSee('placeholder="Tuliskan deskripsi singkat posisi"', false);
        $response->assertDontSee('>Menjalankan operasional SDM.<', false);
        $response->assertSee('value="'.$tenant->id.'"', false);
        $response->assertSee($tenant->name);
        $response->assertSee('value="'.$department->id.'"', false);
        $response->assertSee($department->name);
        $response->assertDontSee('value="'.$tenant->id.'" selected', false);
        $response->assertDontSee('value="'.$department->id.'" selected', false);
        $response->assertSee('<option value="active" selected>Aktif</option>', false);
    }

    public function test_empty_state_muncul_jika_data_kosong(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Empty Posisi',
            'code' => 'TEP-01',
            'slug' => 'tenant-empty-posisi',
            'domain' => 'tenant-empty-posisi.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Empty Posisi',
            'email' => 'admin-empty-posisi@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('positions.index'));

        $response->assertOk();
        $response->assertSee('data-testid="positions-empty-state"', false);
        $response->assertSee('Belum ada posisi, silakan tambah posisi baru');
    }
}