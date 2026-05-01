<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentsDetailTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $admin;
    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Tenant Detail Departemen',
            'code' => 'TEN-DEP-DETAIL',
            'slug' => 'tenant-detail-departemen',
            'domain' => 'tenant-detail-departemen.test',
            'status' => 'active',
        ]);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin Detail Departemen',
            'email' => 'admin-detail-departemen@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $this->department = Department::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Human Capital',
            'code' => 'HC',
            'description' => 'Mengelola kebutuhan SDM dan pengembangan organisasi.',
            'status' => 'active',
        ]);
    }

    public function test_detail_departemen_hanya_menampilkan_informasi_inti(): void
    {
        $supervisor = Position::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Supervisor HC',
            'code' => 'POS-HC-001',
            'status' => 'active',
        ]);

        $staff = Position::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Staff HC',
            'code' => 'POS-HC-002',
            'status' => 'active',
        ]);

        $supervisorEmployee = Employee::create([
            'tenant_id' => $this->tenant->id,
            'department_id' => $this->department->id,
            'position_id' => $supervisor->id,
            'employee_code' => 'EMP-HC-001',
            'name' => 'Budi Supervisor',
            'email' => 'budi-supervisor@example.test',
            'status' => 'active',
        ]);

        $staffEmployee = Employee::create([
            'tenant_id' => $this->tenant->id,
            'department_id' => $this->department->id,
            'position_id' => $staff->id,
            'employee_code' => 'EMP-HC-002',
            'name' => 'Siti Staff',
            'email' => 'siti-staff@example.test',
            'status' => 'active',
        ]);

        $inactiveEmployee = Employee::create([
            'tenant_id' => $this->tenant->id,
            'department_id' => $this->department->id,
            'position_id' => $staff->id,
            'employee_code' => 'EMP-HC-003',
            'name' => 'Rina Staff',
            'email' => 'rina-staff@example.test',
            'status' => 'inactive',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('departments.show', $this->department));

        $response->assertOk();
        $response->assertSee('Human Capital');
        $response->assertSee('Kode:');
        $response->assertSee('HC');
        $response->assertSee('Tenant:');
        $response->assertSee('Tenant Detail Departemen');
        $response->assertSee('Status:');
        $response->assertSee('active');
        $response->assertSee('Deskripsi:');
        $response->assertSee('Mengelola kebutuhan SDM dan pengembangan organisasi.');
        $response->assertDontSee('data-testid="tab-positions"', false);
        $response->assertDontSee('data-testid="tab-employees"', false);
        $response->assertDontSee('data-testid="btn-export-positions-csv"', false);
        $response->assertDontSee('data-testid="btn-export-positions-xlsx"', false);
        $response->assertDontSee('Export CSV');
        $response->assertDontSee('Export XLSX');
        $response->assertDontSee('Tambah Posisi');
        $response->assertDontSee('data-testid="positions-table"', false);
        $response->assertDontSee('data-testid="employees-table"', false);
        $response->assertDontSee('Supervisor HC');
        $response->assertDontSee('POS-HC-001');
        $response->assertDontSee('Staff HC');
        $response->assertDontSee('POS-HC-002');
        $response->assertDontSee('Budi Supervisor');
        $response->assertDontSee('EMP-HC-001');
        $response->assertDontSee('Siti Staff');
        $response->assertDontSee('EMP-HC-002');
        $response->assertDontSee('Rina Staff');
        $response->assertDontSee('EMP-HC-003');
        $response->assertDontSee('data-testid="btn-view-position-'.$supervisor->id.'"', false);
        $response->assertDontSee('data-testid="btn-edit-position-'.$staff->id.'"', false);
        $response->assertDontSee('data-testid="btn-delete-position-'.$staff->id.'"', false);
        $response->assertDontSee('data-testid="btn-view-employee-'.$supervisorEmployee->id.'"', false);
        $response->assertDontSee('data-testid="btn-edit-employee-'.$staffEmployee->id.'"', false);
        $response->assertDontSee('data-testid="btn-delete-employee-'.$inactiveEmployee->id.'"', false);
    }

    public function test_detail_departemen_tidak_menampilkan_section_relasi_saat_kosong(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('departments.show', $this->department));

        $response->assertOk();
        $response->assertSee('Human Capital');
        $response->assertDontSee('data-testid="positions-empty-state"', false);
        $response->assertDontSee('data-testid="employees-empty-state"', false);
        $response->assertDontSee('Belum ada posisi di departemen ini');
        $response->assertDontSee('Belum ada karyawan di departemen ini');
    }
}