<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentsIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_tabel_departemen_muncul(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Departemen',
            'code' => 'TEN-DEP-001',
            'slug' => 'tenant-departemen',
            'domain' => 'tenant-departemen.test',
            'status' => 'active',
        ]);

        $department = Department::create([
            'tenant_id' => $tenant->id,
            'name' => 'Human Capital',
            'code' => 'HC',
            'description' => 'Mengelola SDM dan administrasi karyawan.',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Departemen',
            'email' => 'admin-departemen-index@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('departments.index'));

        $response->assertOk();
        $response->assertSee('Daftar Departemen');
        $response->assertSee('data-testid="departments-table"', false);
        $response->assertSee('opacity-7 text-center', false);
        $response->assertSee('Status');
        $response->assertSee('text-start">Ringkasan', false);
        $response->assertSee('text-start">Action', false);
        $response->assertSee($department->name);
        $response->assertSee('HC');
        $response->assertSee($tenant->name);
        $response->assertSee('Mengelola SDM dan administrasi karyawan.');
    }

    public function test_badge_summary_sesuai_data(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Ringkasan Departemen',
            'code' => 'TEN-DEP-002',
            'slug' => 'tenant-ringkasan-departemen',
            'domain' => 'tenant-ringkasan-departemen.test',
            'status' => 'active',
        ]);

        $department = Department::create([
            'tenant_id' => $tenant->id,
            'name' => 'Operasional',
            'code' => 'OPS',
            'status' => 'active',
        ]);

        $positionA = Position::create([
            'tenant_id' => $tenant->id,
            'name' => 'Supervisor Operasional',
            'status' => 'active',
        ]);

        $positionB = Position::create([
            'tenant_id' => $tenant->id,
            'name' => 'Staf Operasional',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Ringkasan Departemen',
            'email' => 'admin-ringkasan-departemen@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $tenant->id,
            'department_id' => $department->id,
            'position_id' => $positionA->id,
            'employee_code' => 'EMP-DEP-001',
            'name' => 'Karyawan Satu',
            'email' => 'karyawan-dep-satu@example.test',
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $tenant->id,
            'department_id' => $department->id,
            'position_id' => $positionB->id,
            'employee_code' => 'EMP-DEP-002',
            'name' => 'Karyawan Dua',
            'email' => 'karyawan-dep-dua@example.test',
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $tenant->id,
            'department_id' => $department->id,
            'position_id' => $positionB->id,
            'employee_code' => 'EMP-DEP-003',
            'name' => 'Karyawan Tiga',
            'email' => 'karyawan-dep-tiga@example.test',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('departments.index'));

        $response->assertOk();
        $response->assertSee('data-testid="department-employees-count-'.$department->id.'"', false);
        $response->assertSee('data-testid="department-positions-count-'.$department->id.'"', false);
        $response->assertSee('3 Karyawan');
        $response->assertSee('2 Posisi');
    }

    public function test_ikon_aksi_tampil_konsisten(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Aksi Departemen',
            'code' => 'TEN-DEP-003',
            'slug' => 'tenant-aksi-departemen',
            'domain' => 'tenant-aksi-departemen.test',
            'status' => 'active',
        ]);

        $department = Department::create([
            'tenant_id' => $tenant->id,
            'name' => 'Keuangan',
            'code' => 'FIN',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Aksi Departemen',
            'email' => 'admin-aksi-departemen@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('departments.index'));

        $response->assertOk();
        $response->assertSee('data-testid="btn-view-department-'.$department->id.'"', false);
        $response->assertSee('data-testid="btn-edit-department-'.$department->id.'"', false);
        $response->assertSee('data-testid="btn-delete-department-'.$department->id.'"', false);
        $response->assertSee('data-testid="department-index-delete-modal-'.$department->id.'"', false);
        $response->assertSee('data-testid="confirm-delete-department-'.$department->id.'"', false);
        $response->assertSee('Konfirmasi Hapus Departemen');
        $response->assertSee('Apakah Anda yakin ingin menghapus departemen');
        $response->assertSee('fas fa-eye text-info', false);
        $response->assertSee('fas fa-edit text-secondary', false);
        $response->assertSee('fas fa-trash text-danger', false);
        $response->assertDontSee("confirm('Hapus departemen ini?')", false);
    }

    public function test_empty_state_tampil_saat_belum_ada_departemen(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Empty Departemen',
            'code' => 'TEN-DEP-004',
            'slug' => 'tenant-empty-departemen',
            'domain' => 'tenant-empty-departemen.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Empty Departemen',
            'email' => 'admin-empty-departemen@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('departments.index'));

        $response->assertOk();
        $response->assertSee('data-testid="departments-empty-state"', false);
        $response->assertSee('Belum ada departemen, silakan tambah terlebih dahulu');
    }

    public function test_filter_dan_pencarian_departemen_tampil_di_halaman_index(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Filter Departemen',
            'code' => 'TEN-DEP-005',
            'slug' => 'tenant-filter-departemen',
            'domain' => 'tenant-filter-departemen.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Filter Departemen',
            'email' => 'admin-filter-departemen@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('departments.index'));

        $response->assertOk();
        $response->assertSee('data-testid="departments-filter-form"', false);
        $response->assertSee('data-testid="departments-search-input"', false);
        $response->assertSee('data-testid="departments-tenant-filter"', false);
        $response->assertSee('data-testid="departments-status-filter"', false);
        $response->assertSee('data-testid="btn-apply-department-filter"', false);
        $response->assertSee('data-testid="btn-reset-department-filter"', false);
        $response->assertSee('data-testid="btn-import-departments-xlsx"', false);
        $response->assertSee('data-testid="btn-export-departments-xlsx"', false);
        $response->assertSee('Export Excel');
        $response->assertSee('data-testid="btn-download-departments-template"', false);
    }

    public function test_departemen_dapat_dicari_dan_difilter(): void
    {
        $tenantA = Tenant::create([
            'name' => 'Tenant Pusat',
            'code' => 'TEN-DEP-006',
            'slug' => 'tenant-pusat',
            'domain' => 'tenant-pusat.test',
            'status' => 'active',
        ]);

        $tenantB = Tenant::create([
            'name' => 'Tenant Cabang',
            'code' => 'TEN-DEP-007',
            'slug' => 'tenant-cabang',
            'domain' => 'tenant-cabang.test',
            'status' => 'active',
        ]);

        $finance = Department::create([
            'tenant_id' => $tenantA->id,
            'name' => 'Finance & Accounting',
            'code' => 'FIN',
            'description' => 'Laporan keuangan dan budgeting.',
            'status' => 'active',
        ]);

        Department::create([
            'tenant_id' => $tenantA->id,
            'name' => 'Human Capital',
            'code' => 'HC',
            'description' => 'Pengelolaan SDM.',
            'status' => 'inactive',
        ]);

        Department::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Customer Support',
            'code' => 'CS',
            'description' => 'Dukungan pelanggan tenant cabang.',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenantA->id,
            'name' => 'Admin Cari Departemen',
            'email' => 'admin-cari-departemen@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('departments.index', [
            'search' => 'FIN',
            'tenant_id' => $tenantA->id,
            'status' => 'active',
        ]));

        $response->assertOk();
        $response->assertSee($finance->name);
        $response->assertDontSee('Human Capital');
        $response->assertDontSee('Customer Support');
        $response->assertSee('Pencarian: FIN');
        $response->assertSee('Tenant: '.$tenantA->name);
        $response->assertSee('Status: Aktif');
    }

    public function test_departemen_tetap_bisa_diurutkan_lewat_query_string(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Sort Departemen',
            'code' => 'TEN-DEP-008',
            'slug' => 'tenant-sort-departemen',
            'domain' => 'tenant-sort-departemen.test',
            'status' => 'active',
        ]);

        Department::create([
            'tenant_id' => $tenant->id,
            'name' => 'Zulu Department',
            'code' => 'ZUL',
            'status' => 'active',
        ]);

        Department::create([
            'tenant_id' => $tenant->id,
            'name' => 'Alpha Department',
            'code' => 'ALP',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Sort Departemen',
            'email' => 'admin-sort-departemen@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('departments.index', [
            'sort_by' => 'name',
            'sort_direction' => 'asc',
        ]));

        $response->assertOk();
        $response->assertDontSee('Urutkan: Nama departemen (ASC)');
        $this->assertLessThan(
            strpos($response->getContent(), 'Zulu Department'),
            strpos($response->getContent(), 'Alpha Department')
        );
    }
}
