<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentsEditViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_department_page_matches_create_layout_and_prefills_fields(): void
    {
        $tenant = Tenant::create([
            'name' => 'Department Edit Tenant',
            'code' => 'DET-001',
            'slug' => 'department-edit-tenant',
            'domain' => 'department-edit-tenant.test',
            'status' => 'active',
        ]);

        $department = Department::create([
            'tenant_id' => $tenant->id,
            'name' => 'Human Resources Development',
            'code' => 'HRD',
            'description' => 'Mengelola pengembangan SDM.',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Department Edit Admin',
            'email' => 'department-edit-admin@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('departments.edit', $department));

        $response->assertOk();
        $response->assertSee('data-testid="departments-edit-card"', false);
        $response->assertSee('data-testid="departments-edit-form"', false);
        $response->assertSee('Edit Departemen');
        $response->assertSee('Perbarui nama, tenant, kode internal, dan status operasional departemen.');
        $response->assertSee('fas fa-save me-1', false);
        $response->assertSee('Simpan Perubahan');
        $response->assertSee('Batal');
        $response->assertSee('value="Human Resources Development"', false);
        $response->assertSee('value="HRD"', false);
        $response->assertSee('Mengelola pengembangan SDM.');
        $response->assertSee('>Department Edit Tenant<', false);
    }
}