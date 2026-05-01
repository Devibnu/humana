<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantsIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_tabel_tenant_muncul(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Satu',
            'code' => 'TNT-001',
            'slug' => 'tenant-satu',
            'domain' => 'tenant-satu.test',
            'status' => 'active',
            'address' => 'Jl. Mawar No. 1',
            'contact' => '08123456789',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Tenant',
            'email' => 'admin-tenant-index@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('tenants.index'));

        $response->assertOk();
        $response->assertSee('Daftar Tenant');
        $response->assertSee('data-testid="tenants-summary-total"', false);
        $response->assertSee('data-testid="tenants-summary-active"', false);
        $response->assertSee('data-testid="tenants-summary-inactive"', false);
        $response->assertSee('data-testid="tenants-filter-form"', false);
        $response->assertSee('data-testid="tenants-table"', false);
        $response->assertSee('Tenant Satu');
        $response->assertSee('TNT-001');
        $response->assertSee('Jl. Mawar No. 1');
        $response->assertSee('08123456789');
    }

    public function test_badge_summary_sesuai_data(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Ringkasan',
            'code' => 'TNT-002',
            'slug' => 'tenant-ringkasan',
            'domain' => 'tenant-ringkasan.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Ringkasan',
            'email' => 'admin-ringkasan@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        User::create([
            'tenant_id' => $tenant->id,
            'name' => 'User Tambahan',
            'email' => 'user-tambahan@example.test',
            'password' => 'password',
            'role' => 'employee',
            'status' => 'active',
        ]);

        Department::create([
            'tenant_id' => $tenant->id,
            'name' => 'Operasional',
            'status' => 'active',
        ]);

        Department::create([
            'tenant_id' => $tenant->id,
            'name' => 'Keuangan',
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'EMP-TNT-001',
            'name' => 'Karyawan Satu',
            'email' => 'karyawan-satu@example.test',
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'EMP-TNT-002',
            'name' => 'Karyawan Dua',
            'email' => 'karyawan-dua@example.test',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('tenants.index'));

        $response->assertOk();
        $response->assertSee('data-testid="tenant-users-count-'.$tenant->id.'"', false);
        $response->assertSee('2 User');
        $response->assertSee('2 Karyawan');
        $response->assertSee('2 Departemen');
    }

    public function test_tombol_crud_tampil(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Aksi',
            'code' => 'TNT-003',
            'slug' => 'tenant-aksi',
            'domain' => 'tenant-aksi.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Aksi',
            'email' => 'admin-aksi@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('tenants.index'));

        $response->assertOk();
        $response->assertDontSee('data-testid="btn-add-tenant"', false);
        $response->assertSee('data-testid="tenant-limit-badge"', false);
        $response->assertSee('data-testid="btn-view-tenant-'.$tenant->id.'"', false);
        $response->assertSee('data-testid="btn-edit-tenant-'.$tenant->id.'"', false);
        $response->assertSee('data-testid="btn-delete-tenant-'.$tenant->id.'"', false);
        $response->assertSee('data-testid="tenant-index-delete-modal-'.$tenant->id.'"', false);
        $response->assertSee('data-testid="confirm-delete-tenant-form-'.$tenant->id.'"', false);
        $response->assertSee('Maksimum 1 tenant');
        $response->assertSee('fas fa-eye text-info', false);
        $response->assertSee('fas fa-edit text-secondary', false);
        $response->assertSee('fas fa-trash text-danger', false);
        $response->assertSee('Konfirmasi Hapus Tenant');
    }

    public function test_empty_state_tampil_saat_belum_ada_tenant(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Admin',
            'code' => 'TNT-ADM',
            'slug' => 'tenant-admin',
            'domain' => 'tenant-admin.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Empty',
            'email' => 'admin-empty@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        Tenant::query()->delete();

        $response = $this->actingAs($admin)->get(route('tenants.index'));

        $response->assertOk();
        $response->assertSee('data-testid="tenant-empty-state"', false);
        $response->assertSee('Belum ada tenant, silakan tambah terlebih dahulu');
        $response->assertSee('data-testid="btn-add-tenant"', false);
    }

    public function test_tenant_dapat_dicari_dan_difilter_berdasarkan_status(): void
    {
        $tenantActive = Tenant::create([
            'name' => 'Tenant Aktif Filter',
            'code' => 'TNT-004',
            'slug' => 'tenant-aktif-filter',
            'domain' => 'tenant-aktif-filter.test',
            'status' => 'active',
        ]);

        $tenantInactive = Tenant::create([
            'name' => 'Tenant Nonaktif Filter',
            'code' => 'TNT-005',
            'slug' => 'tenant-nonaktif-filter',
            'domain' => 'tenant-nonaktif-filter.test',
            'status' => 'inactive',
        ]);

        $admin = User::create([
            'tenant_id' => $tenantActive->id,
            'name' => 'Admin Filter Tenant',
            'email' => 'admin-filter-tenant@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('tenants.index', [
            'search' => 'Aktif',
            'status' => 'active',
        ]));

        $response->assertOk();
        $response->assertSee('Filter aktif');
        $response->assertSee('Pencarian: Aktif');
        $response->assertSee('Status: Aktif');
        $response->assertSee('Tenant Aktif Filter');
        $response->assertDontSee('Tenant Nonaktif Filter');
    }

    public function test_empty_state_filter_tenant_tampil_saat_hasil_tidak_ada(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Admin Filter Empty',
            'code' => 'TNT-006',
            'slug' => 'tenant-admin-filter-empty',
            'domain' => 'tenant-admin-filter-empty.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Filter Empty Tenant',
            'email' => 'admin-filter-empty-tenant@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('tenants.index', [
            'search' => 'TidakAdaTenant',
        ]));

        $response->assertOk();
        $response->assertSee('data-testid="tenants-filter-empty-state"', false);
        $response->assertSee('Tidak ada tenant yang cocok dengan pencarian atau filter saat ini.');
    }
}