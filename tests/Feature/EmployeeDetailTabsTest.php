<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeDetailTabsTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $admin;
    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);

        $this->tenant = Tenant::create([
            'name' => 'Employee Detail Tabs Tenant',
            'slug' => 'employee-detail-tabs-tenant',
            'domain' => 'employee-detail-tabs-tenant.test',
            'status' => 'active',
        ]);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Employee Detail Tabs Admin',
            'email' => 'employee-detail-tabs-admin@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $this->employee = Employee::create([
            'tenant_id' => $this->tenant->id,
            'employee_code' => 'EMP-TABS-001',
            'name' => 'Employee Detail Tabs Target',
            'email' => 'employee-detail-tabs-target@example.test',
            'status' => 'active',
        ]);
    }

    public function test_employee_show_page_displays_requested_tabs_and_empty_states(): void
    {
        $response = $this->actingAs($this->admin)->get(route('employees.show', $this->employee));

        $response->assertOk();
        $response->assertSee('Data Karyawan');
        $response->assertSee('Data Keluarga');
        $response->assertSee('Informasi Keuangan');
        $response->assertSee('data-testid="tab-employee-data"', false);
        $response->assertSee('data-testid="tab-family"', false);
        $response->assertSee('data-testid="tab-bank"', false);
        $response->assertSee('fas fa-users me-1', false);
        $response->assertSee('fas fa-money-bill me-1', false);
        $response->assertSee('Belum ada data keluarga');
        $response->assertSee('Belum ada rekening bank');
        $response->assertSee('Tambah Anggota Keluarga');
        $response->assertSee('Tambah Rekening');
        $response->assertSee('data-testid="family-member-modal"', false);
        $response->assertSee('data-testid="form-add-bank"', false);
    }
}