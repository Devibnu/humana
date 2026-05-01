<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentTableAlignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_table_alignment_for_code_and_tenant(): void
    {
        $tenant = Tenant::create([
            'name' => 'JS',
            'code' => 'JS',
            'slug' => 'js',
            'domain' => 'js.test',
            'status' => 'active',
        ]);

        Department::create([
            'tenant_id' => $tenant->id,
            'name' => 'Human Resources Development',
            'code' => 'HRD',
            'description' => 'Departemen pengembangan SDM.',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin HR Alignment',
            'email' => 'admin-department-alignment@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('departments.index'));

        $response->assertOk();
        $response->assertSee('Nama Departemen');
        $response->assertSee('Kode');
        $response->assertSee('Tenant');
        $response->assertSee('text-start">Kode', false);
        $response->assertSee('text-start">Tenant', false);
        $response->assertSee('Human Resources Development');
        $response->assertSee('HRD');
        $response->assertSee('JS');
    }
}