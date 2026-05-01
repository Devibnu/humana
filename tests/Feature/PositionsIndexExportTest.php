<?php

namespace Tests\Feature;

use App\Exports\PositionsExport;
use App\Models\Department;
use App\Models\Position;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class PositionsIndexExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_xlsx_index_memakai_judul_summary_posisi(): void
    {
        Carbon::setTestNow('2026-04-23 10:00:00');
        Excel::fake();

        $tenant = Tenant::create([
            'name' => 'Tenant Xlsx Posisi',
            'code' => 'TXP-01',
            'slug' => 'tenant-xlsx-posisi',
            'domain' => 'tenant-xlsx-posisi.test',
            'status' => 'active',
        ]);

        $department = Department::create([
            'tenant_id' => $tenant->id,
            'name' => 'Keuangan',
            'code' => 'FIN',
            'status' => 'active',
        ]);

        Position::create([
            'tenant_id' => $tenant->id,
            'department_id' => $department->id,
            'name' => 'Analis Keuangan',
            'code' => 'FIN-01',
            'description' => 'Menganalisis laporan keuangan.',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin XLSX Posisi',
            'email' => 'admin-xlsx-posisi@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('positions.export.xlsx'));

        $response->assertOk();

        Excel::assertDownloaded('positions_20260423.xlsx', function (PositionsExport $export) {
            $sheets = $export->sheets();
            $rows = $sheets[0]->collection()->values();

            $this->assertSame('Summary Export Posisi', $rows[0][0]);
            $this->assertSame('Analis Keuangan', $rows[5][0]);

            return true;
        });
    }
}