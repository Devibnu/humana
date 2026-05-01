<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeesEditViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_employee_form_uses_shared_layout_and_prefills_fields(): void
    {
        $tenant = Tenant::create([
            'name' => 'Employee Edit View Tenant',
            'slug' => 'employee-edit-view-tenant',
            'domain' => 'employee-edit-view-tenant.test',
            'status' => 'active',
        ]);

        $department = Department::create([
            'tenant_id' => $tenant->id,
            'name' => 'Engineering',
            'status' => 'active',
        ]);

        $position = Position::create([
            'tenant_id' => $tenant->id,
            'name' => 'Backend Engineer',
            'status' => 'active',
        ]);

        $workLocation = WorkLocation::create([
            'tenant_id' => $tenant->id,
            'name' => 'HQ Office',
            'address' => 'Jakarta',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'radius' => 250,
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Employee Edit View Admin',
            'email' => 'employee-edit-view-admin@example.test',
            'password' => 'password123',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'EMP-EDIT-200',
            'name' => 'Edit View Employee',
            'email' => 'edit-view-employee@example.test',
            'phone' => '081234567890',
            'department_id' => $department->id,
            'position_id' => $position->id,
            'work_location_id' => $workLocation->id,
            'role' => 'staff',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('employees.edit', $employee));

        $response->assertOk();
        $response->assertSee('card mx-4 mb-4 shadow-xs', false);
        $response->assertSee('Ubah Data Karyawan');
        $response->assertSee('Informasi Personal');
        $response->assertSee('Informasi Pekerjaan');
        $response->assertSee('Lokasi Kerja');
        $response->assertSee('Koneksi Akun');
        $response->assertSee('Lihat Detail Karyawan');
        $response->assertSee('value="Edit View Employee"', false);
        $response->assertSee('value="EMP-EDIT-200"', false);
        $response->assertSee('value="edit-view-employee@example.test"', false);
        $response->assertSee('value="081234567890"', false);
        $response->assertSee('Simpan Perubahan');
    }
}