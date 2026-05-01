<?php

namespace Tests\Feature;

use App\Exports\UsersExport;
use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class UsersExportXlsxStyledTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_export_sheet_title_matches_filters_and_header_is_styled(): void
    {
        $tenant = Tenant::create([
            'name' => 'Users Styled Tenant',
            'slug' => 'users-styled-tenant',
            'domain' => 'users-styled-tenant.test',
            'status' => 'active',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Styled Export User',
            'email' => 'styled-export-user@example.test',
            'password' => 'password',
            'role' => 'employee',
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'employee_code' => 'USR-STY-1',
            'name' => 'Styled Export Employee',
            'email' => 'styled-export-employee@example.test',
            'status' => 'active',
        ]);

        $export = new UsersExport(User::with(['tenant', 'employee'])->get(), [
            'tenant' => $tenant->id,
            'tenant_name' => $tenant->name,
            'role' => 'employee',
            'linked' => 'only',
        ]);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $styles = $export->styles($sheet);

        $this->assertStringContainsString('Users Users Styled Tenant', $export->title());
        $this->assertLessThanOrEqual(31, strlen($export->title()));
        $this->assertTrue($styles[1]['font']['bold']);
        $this->assertSame('FFFFFF', $styles[1]['font']['color']['rgb']);
        $this->assertSame('1F4E78', $styles[1]['fill']['startColor']['rgb']);
        $this->assertSame('linked_employee_code', $export->headings()[6]);
    }
}