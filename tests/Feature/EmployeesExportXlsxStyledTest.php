<?php

namespace Tests\Feature;

use App\Exports\EmployeesExport;
use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class EmployeesExportXlsxStyledTest extends TestCase
{
    use RefreshDatabase;

    public function test_employees_export_sheet_title_matches_filters_and_header_is_styled(): void
    {
        $tenant = Tenant::create([
            'name' => 'Employees Styled Tenant',
            'slug' => 'employees-styled-tenant',
            'domain' => 'employees-styled-tenant.test',
            'status' => 'active',
        ]);

        $linkedUser = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Styled Employee User',
            'email' => 'styled-employee-user@example.test',
            'password' => 'password',
            'role' => 'employee',
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $linkedUser->id,
            'employee_code' => 'EMP-STY-1',
            'name' => 'Styled Employee Export',
            'email' => 'styled-employee-export@example.test',
            'status' => 'active',
        ]);

        $export = new EmployeesExport(Employee::with(['tenant', 'user', 'position', 'department'])->get(), [
            'tenant' => $tenant->id,
            'tenant_name' => $tenant->name,
            'linked' => 'only',
        ]);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $styles = $export->styles($sheet);

        $this->assertStringContainsString('Employees Employees Styled', $export->title());
        $this->assertLessThanOrEqual(31, strlen($export->title()));
        $this->assertTrue($styles[1]['font']['bold']);
        $this->assertSame('FFFFFF', $styles[1]['font']['color']['rgb']);
        $this->assertSame('0F766E', $styles[1]['fill']['startColor']['rgb']);
        $this->assertSame('linked_user_email', $export->headings()[4]);
    }
}