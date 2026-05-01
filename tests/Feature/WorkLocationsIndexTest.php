<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkLocationsIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_work_locations_menggunakan_layout_yang_konsisten(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Lokasi Kerja UI',
            'code' => 'TEN-WL-001',
            'slug' => 'tenant-lokasi-kerja-ui',
            'domain' => 'tenant-lokasi-kerja-ui.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Lokasi Kerja UI',
            'email' => 'admin-lokasi-kerja-ui@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $workLocation = WorkLocation::create([
            'tenant_id' => $tenant->id,
            'name' => 'Kantor Pusat',
            'address' => 'Jl. Sudirman No. 1',
            'latitude' => -6.2,
            'longitude' => 106.8166667,
            'radius' => 150,
        ]);

        $response = $this->actingAs($admin)->get(route('work_locations.index'));

        $response->assertOk();
        $response->assertSee('Daftar Lokasi Kerja');
        $response->assertSee('data-testid="work-locations-summary-total"', false);
        $response->assertSee('data-testid="work-locations-summary-tenant-count"', false);
        $response->assertSee('data-testid="work-locations-summary-average-radius"', false);
        $response->assertSee('data-testid="work-locations-filter-form"', false);
        $response->assertSee('data-testid="work-locations-search-input"', false);
        $response->assertSee('data-testid="work-locations-tenant-filter"', false);
        $response->assertSee('data-testid="btn-open-create-work-location"', false);
        $response->assertSee('data-testid="work-locations-table"', false);
        $response->assertSee('data-testid="btn-edit-work-location-'.$workLocation->id.'"', false);
        $response->assertSee('data-testid="btn-delete-work-location-'.$workLocation->id.'"', false);
        $response->assertSee('data-testid="work-location-index-delete-modal-'.$workLocation->id.'"', false);
        $response->assertSee('data-testid="confirm-delete-work-location-'.$workLocation->id.'"', false);
        $response->assertSee('Konfirmasi Hapus Lokasi Kerja');
        $response->assertDontSee("confirm('Delete this work location?')", false);
    }

    public function test_index_work_locations_dapat_dicari_dan_difilter_berdasarkan_tenant(): void
    {
        $tenantA = Tenant::create([
            'name' => 'Tenant A Lokasi Kerja',
            'code' => 'TEN-WL-002',
            'slug' => 'tenant-a-lokasi-kerja',
            'domain' => 'tenant-a-lokasi-kerja.test',
            'status' => 'active',
        ]);

        $tenantB = Tenant::create([
            'name' => 'Tenant B Lokasi Kerja',
            'code' => 'TEN-WL-003',
            'slug' => 'tenant-b-lokasi-kerja',
            'domain' => 'tenant-b-lokasi-kerja.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenantA->id,
            'name' => 'Admin Filter Lokasi Kerja',
            'email' => 'admin-filter-lokasi-kerja@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        WorkLocation::create([
            'tenant_id' => $tenantA->id,
            'name' => 'Gudang Barat',
            'address' => 'Jakarta Barat',
            'latitude' => -6.15,
            'longitude' => 106.75,
            'radius' => 100,
        ]);

        WorkLocation::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Cabang Timur',
            'address' => 'Jakarta Timur',
            'latitude' => -6.21,
            'longitude' => 106.91,
            'radius' => 250,
        ]);

        $response = $this->actingAs($admin)->get(route('work_locations.index', [
            'search' => 'Gudang',
            'tenant_id' => $tenantA->id,
        ]));

        $response->assertOk();
        $response->assertSee('Filter aktif');
        $response->assertSee('Pencarian: Gudang');
        $response->assertSee('Tenant: '.$tenantA->name);
        $response->assertSee('Gudang Barat');
        $response->assertDontSee('Cabang Timur');
    }

    public function test_empty_state_filter_tampil_saat_hasil_tidak_ditemukan(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Empty Filter Lokasi Kerja',
            'code' => 'TEN-WL-004',
            'slug' => 'tenant-empty-filter-lokasi-kerja',
            'domain' => 'tenant-empty-filter-lokasi-kerja.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Empty Filter Lokasi Kerja',
            'email' => 'admin-empty-filter-lokasi-kerja@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        WorkLocation::create([
            'tenant_id' => $tenant->id,
            'name' => 'Kantor Selatan',
            'address' => 'Jakarta Selatan',
            'latitude' => -6.26,
            'longitude' => 106.81,
            'radius' => 180,
        ]);

        $response = $this->actingAs($admin)->get(route('work_locations.index', [
            'search' => 'TidakAda',
        ]));

        $response->assertOk();
        $response->assertSee('data-testid="work-locations-filter-empty-state"', false);
        $response->assertSee('Tidak ada lokasi kerja yang cocok dengan pencarian atau filter saat ini.');
    }
}