<?php

namespace Tests\Feature;

use App\Exports\DepartmentsExport;
use App\Models\Department;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class DepartmentsExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_hr_can_export_departments_csv_with_active_filters(): void
    {
        Carbon::setTestNow('2026-04-23 09:00:00');
        Excel::fake();

        $tenantA = $this->makeTenant('tenant-departemen-export-a');
        $tenantB = $this->makeTenant('tenant-departemen-export-b');
        $admin = $this->makeUser('admin_hr', $tenantA, 'departments-export-admin@example.test', 'Departments Export Admin');

        Department::create([
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
            'description' => 'Dukungan pelanggan tenant lain.',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('departments.export', [
            'tenant_id' => $tenantA->id,
            'status' => 'active',
            'search' => 'FIN',
            'sort_by' => 'name',
            'sort_direction' => 'asc',
        ]));

        $response->assertOk();

        Excel::assertDownloaded('departments-export_20260423_tenant-tenant-departemen-export-a_status-active_search-fin_sort-name-asc.csv', function (DepartmentsExport $export) {
            $rows = $export->collection()->toArray();

            $this->assertCount(1, $rows);
            $this->assertSame('Finance & Accounting', $rows[0]['name']);
            $this->assertSame('FIN', $rows[0]['code']);
            $this->assertSame('active', $rows[0]['status']);

            return true;
        });
    }

    public function test_admin_hr_can_export_departments_xlsx_with_sorting_and_filters(): void
    {
        Carbon::setTestNow('2026-04-23 09:00:00');
        Excel::fake();

        $tenant = $this->makeTenant('tenant-departemen-export-xlsx');
        $admin = $this->makeUser('admin_hr', $tenant, 'departments-export-xlsx-admin@example.test', 'Departments Export Xlsx Admin');

        Department::create([
            'tenant_id' => $tenant->id,
            'name' => 'Research & Development',
            'code' => 'RND',
            'description' => 'Eksperimen dan inovasi.',
            'status' => 'inactive',
        ]);

        Department::create([
            'tenant_id' => $tenant->id,
            'name' => 'Accounting',
            'code' => 'ACC',
            'description' => 'Pencatatan akuntansi.',
            'status' => 'inactive',
        ]);

        $response = $this->actingAs($admin)->get(route('departments.export', [
            'tenant_id' => $tenant->id,
            'status' => 'inactive',
            'sort_by' => 'name',
            'sort_direction' => 'asc',
            'format' => 'xlsx',
        ]));

        $response->assertOk();

        Excel::assertDownloaded('departments-export_20260423_tenant-tenant-departemen-export-xlsx_status-inactive_sort-name-asc.xlsx', function (DepartmentsExport $export) {
            $rows = $export->collection()->toArray();

            $this->assertCount(2, $rows);
            $this->assertSame('Accounting', $rows[0]['name']);
            $this->assertSame('Research & Development', $rows[1]['name']);

            return true;
        });
    }

    public function test_employee_cannot_export_departments(): void
    {
        $tenant = $this->makeTenant('tenant-departemen-export-forbidden');
        $employee = $this->makeUser('employee', $tenant, 'departments-export-employee@example.test', 'Departments Export Employee');

        $this->actingAs($employee)
            ->get(route('departments.export'))
            ->assertForbidden();
    }

    protected function makeTenant(string $slug): Tenant
    {
        return Tenant::create([
            'name' => ucwords(str_replace('-', ' ', $slug)),
            'slug' => $slug,
            'domain' => $slug.'.test',
            'status' => 'active',
        ]);
    }

    protected function makeUser(string $role, Tenant $tenant, string $email, string $name): User
    {
        return User::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'email' => $email,
            'password' => 'password',
            'role' => $role,
            'status' => 'active',
        ]);
    }
}
