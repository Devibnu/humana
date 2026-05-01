<?php

namespace Tests\Feature;

use App\Exports\DepartmentsImportTemplateExport;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class DepartmentsImportTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_hr_can_download_departments_import_template(): void
    {
        Carbon::setTestNow('2026-04-23 10:00:00');
        Excel::fake();

        $tenant = Tenant::create([
            'name' => 'Tenant Template Departemen',
            'code' => 'TEN-TPL-DEP',
            'slug' => 'tenant-template-departemen',
            'domain' => 'tenant-template-departemen.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Template Departemen',
            'email' => 'admin-template-departemen@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('departments.import.template'));

        $response->assertOk();

        Excel::assertDownloaded('departments-import-template_20260423.xlsx', function (DepartmentsImportTemplateExport $export) {
            $rows = $export->array();

            $this->assertCount(2, $rows);
            $this->assertSame('TENANT-A', $rows[0][0]);
            $this->assertSame('Finance & Accounting', $rows[0][1]);

            return true;
        });
    }

    public function test_employee_cannot_download_departments_import_template(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Template Forbidden',
            'code' => 'TEN-TPL-FBD',
            'slug' => 'tenant-template-forbidden',
            'domain' => 'tenant-template-forbidden.test',
            'status' => 'active',
        ]);

        $employee = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Employee Template Forbidden',
            'email' => 'employee-template-forbidden@example.test',
            'password' => 'password',
            'role' => 'employee',
            'status' => 'active',
        ]);

        $this->actingAs($employee)
            ->get(route('departments.import.template'))
            ->assertForbidden();
    }
}
