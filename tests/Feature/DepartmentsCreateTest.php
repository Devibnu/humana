<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentsCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_form_tampil_dengan_field_lengkap(): void
    {
        $tenant = Tenant::create([
            'name' => 'Department Tenant',
            'code' => 'DPT-001',
            'slug' => 'department-tenant',
            'domain' => 'department-tenant.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Department Admin',
            'email' => 'department-admin@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('departments.create'));

        $response->assertOk();
        $response->assertSee('Tambah Departemen Baru');
        $response->assertSee('data-testid="departments-create-form"', false);
        $response->assertSee('name="name"', false);
        $response->assertSee('name="code"', false);
        $response->assertSee('name="tenant_id"', false);
        $response->assertSee('name="description"', false);
        $response->assertSee('name="status"', false);
        $response->assertSee('fas fa-save me-1', false);
        $response->assertSee('fas fa-times me-1', false);
        $response->assertSee($tenant->name);
    }

    public function test_validasi_name_dan_tenant_wajib(): void
    {
        $tenant = Tenant::create([
            'name' => 'Department Tenant Validation',
            'code' => 'DPT-002',
            'slug' => 'department-tenant-validation',
            'domain' => 'department-tenant-validation.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Department Validation Admin',
            'email' => 'department-validation-admin@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post(route('departments.store'), [
                'status' => 'active',
            ])
            ->assertSessionHasErrors(['name', 'tenant_id']);
    }

    public function test_department_baru_tersimpan_dengan_tenant(): void
    {
        $tenant = Tenant::create([
            'name' => 'Department Tenant Save',
            'code' => 'DPT-003',
            'slug' => 'department-tenant-save',
            'domain' => 'department-tenant-save.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Department Save Admin',
            'email' => 'department-save-admin@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('departments.store'), [
                'tenant_id' => $tenant->id,
                'name' => 'Human Capital',
                'code' => 'HC',
                'description' => 'Mengelola proses SDM dan administrasi karyawan.',
                'status' => 'active',
            ]);

        $response->assertRedirect(route('departments.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('departments', [
            'tenant_id' => $tenant->id,
            'name' => 'Human Capital',
            'code' => 'HC',
            'description' => 'Mengelola proses SDM dan administrasi karyawan.',
            'status' => 'active',
        ]);
    }

    public function test_success_flash_is_only_rendered_once_in_departments_index(): void
    {
        $tenant = Tenant::create([
            'name' => 'Department Tenant Flash',
            'code' => 'DPT-003A',
            'slug' => 'department-tenant-flash',
            'domain' => 'department-tenant-flash.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Department Flash Admin',
            'email' => 'department-flash-admin@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['success' => 'Departemen berhasil ditambahkan.'])
            ->get(route('departments.index'));

        $response->assertOk();
        $response->assertSee('Departemen berhasil ditambahkan.');
        $this->assertSame(1, substr_count($response->getContent(), 'Departemen berhasil ditambahkan.'));
    }

    public function test_empty_state_muncul_jika_tenant_kosong(): void
    {
        $tenant = Tenant::create([
            'name' => 'Department Tenant Empty',
            'code' => 'DPT-004',
            'slug' => 'department-tenant-empty',
            'domain' => 'department-tenant-empty.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Department Empty Admin',
            'email' => 'department-empty-admin@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        Tenant::query()->delete();

        $response = $this->actingAs($admin)->get(route('departments.create'));

        $response->assertOk();
        $response->assertSee('data-testid="departments-create-empty-state"', false);
        $response->assertSee('Belum ada tenant tersedia');
        $response->assertDontSee('data-testid="departments-create-form"', false);
    }
}