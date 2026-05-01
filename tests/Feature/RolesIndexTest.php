<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolesIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_roles_menggunakan_layout_yang_konsisten(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Role UI',
            'code' => 'TEN-ROLE-001',
            'slug' => 'tenant-role-ui',
            'domain' => 'tenant-role-ui.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Role UI',
            'email' => 'admin-role-ui@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $role = Role::create([
            'name' => 'Supervisor Shift',
            'description' => 'Mengelola operasional shift dan absensi tim.',
        ]);

        RolePermission::create([
            'role_id' => $role->id,
            'menu_key' => 'attendances',
            'can_access' => true,
        ]);

        User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Pengguna Supervisor Shift',
            'email' => 'pengguna-supervisor-shift@example.test',
            'password' => 'password',
            'role' => 'supervisor_shift',
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('roles.index'));

        $response->assertOk();
        $response->assertSee('Daftar Role');
        $response->assertSee('data-testid="roles-summary-total"', false);
        $response->assertSee('data-testid="roles-summary-assigned"', false);
        $response->assertSee('data-testid="roles-summary-permissions"', false);
        $response->assertSee('data-testid="roles-filter-form"', false);
        $response->assertSee('data-testid="roles-search-input"', false);
        $response->assertSee('data-testid="roles-usage-filter"', false);
        $response->assertSee('data-testid="btn-add-role"', false);
        $response->assertSee('data-testid="roles-table"', false);
        $response->assertSee('SUPERVISOR SHIFT');
        $response->assertSee('Mengelola operasional shift dan absensi tim.');
        $response->assertSee('data-testid="role-permissions-count-'.$role->id.'"', false);
        $response->assertSee('data-testid="role-users-count-'.$role->id.'"', false);
        $response->assertSee('data-testid="btn-edit-role-'.$role->id.'"', false);
        $response->assertSee('data-testid="btn-delete-role-'.$role->id.'"', false);
        $response->assertSee('data-testid="role-index-delete-modal-'.$role->id.'"', false);
        $response->assertSee('data-testid="confirm-delete-role-form-'.$role->id.'"', false);
        $response->assertSee('Konfirmasi Hapus Role');
    }

    public function test_role_dapat_dicari_dan_difilter_berdasarkan_penggunaan(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Role Filter',
            'code' => 'TEN-ROLE-002',
            'slug' => 'tenant-role-filter',
            'domain' => 'tenant-role-filter.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Role Filter',
            'email' => 'admin-role-filter@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $assignedRole = Role::create([
            'name' => 'Reviewer Payroll',
            'description' => 'Memeriksa payroll sebelum finalisasi.',
        ]);

        $unusedRole = Role::create([
            'name' => 'Arsip Legal',
            'description' => 'Mengelola arsip legal perusahaan.',
        ]);

        User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Pengguna Reviewer Payroll',
            'email' => 'pengguna-reviewer-payroll@example.test',
            'password' => 'password',
            'role' => 'reviewer_payroll',
            'role_id' => $assignedRole->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('roles.index', [
            'search' => 'Reviewer',
            'usage' => 'assigned',
        ]));

        $response->assertOk();
        $response->assertSee('Filter aktif');
        $response->assertSee('Pencarian: Reviewer');
        $response->assertSee('Penggunaan: Sedang Dipakai');
        $response->assertSee('REVIEWER PAYROLL');
        $response->assertDontSee('ARSIP LEGAL');
    }

    public function test_empty_state_filter_roles_tampil_saat_hasil_tidak_ada(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Role Empty',
            'code' => 'TEN-ROLE-003',
            'slug' => 'tenant-role-empty',
            'domain' => 'tenant-role-empty.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Role Empty',
            'email' => 'admin-role-empty@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('roles.index', [
            'search' => 'TidakAdaRole',
        ]));

        $response->assertOk();
        $response->assertSee('data-testid="roles-filter-empty-state"', false);
        $response->assertSee('Tidak ada role yang cocok dengan pencarian atau filter saat ini.');
    }
}