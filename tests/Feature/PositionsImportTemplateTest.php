<?php

namespace Tests\Feature;

use App\Exports\PositionsImportTemplateExport;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class PositionsImportTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_hr_can_download_positions_import_template(): void
    {
        Carbon::setTestNow('2026-04-23 10:00:00');
        Excel::fake();

        $tenant = Tenant::create([
            'name' => 'Tenant Template Posisi',
            'code' => 'TEN-TPL-POS',
            'slug' => 'tenant-template-posisi',
            'domain' => 'tenant-template-posisi.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Template Posisi',
            'email' => 'admin-template-posisi@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('positions.import.template'));

        $response->assertOk();

        Excel::assertDownloaded('positions-import-template_20260423.xlsx', function (PositionsImportTemplateExport $export) {
            $rows = $export->array();

            $this->assertCount(2, $rows);
            $this->assertSame('TENANT-A', $rows[0][0]);
            $this->assertSame('FIN', $rows[0][1]);
            $this->assertSame('Finance Manager', $rows[0][2]);

            return true;
        });
    }

    public function test_employee_cannot_download_positions_import_template(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Template Posisi Forbidden',
            'code' => 'TEN-TPL-POS-FBD',
            'slug' => 'tenant-template-posisi-forbidden',
            'domain' => 'tenant-template-posisi-forbidden.test',
            'status' => 'active',
        ]);

        $employee = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Employee Template Posisi Forbidden',
            'email' => 'employee-template-posisi-forbidden@example.test',
            'password' => 'password',
            'role' => 'employee',
            'status' => 'active',
        ]);

        $this->actingAs($employee)
            ->get(route('positions.import.template'))
            ->assertForbidden();
    }
}