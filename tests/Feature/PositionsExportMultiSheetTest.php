<?php

namespace Tests\Feature;

use App\Exports\PositionsEmployeeSummarySheetExport;
use App\Exports\PositionsExport;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class PositionsExportMultiSheetTest extends TestCase
{
    use RefreshDatabase;

    public function test_file_xlsx_memiliki_dua_sheet_dan_sheet_kedua_berisi_summary_karyawan_per_posisi(): void
    {
        Carbon::setTestNow('2026-04-20 10:00:00');
        Excel::fake();

        $tenant = Tenant::create([
            'name' => 'Tenant Multi Sheet Posisi',
            'code' => 'TEN-MULTI-POS',
            'slug' => 'tenant-multi-sheet-posisi',
            'domain' => 'tenant-multi-sheet-posisi.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Multi Sheet Posisi',
            'email' => 'admin-multi-sheet-posisi@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $department = Department::create([
            'tenant_id' => $tenant->id,
            'name' => 'Divisi Operasional',
            'code' => 'DVO',
            'description' => 'Mengelola operasional harian.',
            'status' => 'active',
        ]);

        $supervisor = Position::create([
            'tenant_id' => $tenant->id,
            'department_id' => $department->id,
            'name' => 'Supervisor Operasional',
            'code' => 'OPS-01',
            'description' => 'Mengawasi kegiatan operasional.',
            'status' => 'active',
        ]);

        $staff = Position::create([
            'tenant_id' => $tenant->id,
            'department_id' => $department->id,
            'name' => 'Staf Operasional',
            'code' => 'OPS-02',
            'description' => 'Menjalankan tugas operasional.',
            'status' => 'inactive',
        ]);

        Employee::create([
            'tenant_id' => $tenant->id,
            'department_id' => $department->id,
            'position_id' => $supervisor->id,
            'employee_code' => 'EMP-OPS-01',
            'name' => 'Supervisor Satu',
            'email' => 'supervisor-satu@example.test',
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $tenant->id,
            'department_id' => $department->id,
            'position_id' => $staff->id,
            'employee_code' => 'EMP-OPS-02',
            'name' => 'Staf Satu',
            'email' => 'staf-satu@example.test',
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $tenant->id,
            'department_id' => $department->id,
            'position_id' => $staff->id,
            'employee_code' => 'EMP-OPS-03',
            'name' => 'Staf Dua',
            'email' => 'staf-dua@example.test',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('departments.positions.export.xlsx', $department));

        $response->assertOk();

        Excel::assertDownloaded('positions_divisi_operasional_20260420.xlsx', function (PositionsExport $export) {
            $sheets = $export->sheets();

            $this->assertCount(2, $sheets);
            $this->assertSame('Posisi', $sheets[0]->title());
            $this->assertSame('Summary Karyawan', $sheets[1]->title());
            $this->assertSame('Summary Karyawan per Posisi', $sheets[1]->collection()->values()[0][0]);
            $this->assertSame(['Nama Posisi', 'Jumlah Karyawan'], $sheets[1]->collection()->values()[1]);
            $this->assertContains(['Staf Operasional', 2], $sheets[1]->collection()->toArray());
            $this->assertContains(['Supervisor Operasional', 1], $sheets[1]->collection()->toArray());

            return true;
        });
    }

    public function test_sheet_kedua_menerapkan_freeze_header_autofit_dan_zebra_striping(): void
    {
        $sheet = new PositionsEmployeeSummarySheetExport(collect([
            (object) [
                'name' => 'Supervisor Operasional',
                'employees_count' => 1,
            ],
            (object) [
                'name' => 'Staf Operasional',
                'employees_count' => 2,
            ],
        ]));

        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        foreach ($sheet->collection()->values() as $rowIndex => $row) {
            foreach (array_values($row) as $columnIndex => $value) {
                $worksheet->setCellValueByColumnAndRow($columnIndex + 1, $rowIndex + 1, $value);
            }
        }

        $events = $sheet->registerEvents();
        $afterSheet = $events[AfterSheet::class];
        $afterSheet(new class($worksheet) {
            public function __construct(public $worksheet)
            {
            }

            public function __get(string $name)
            {
                if ($name === 'sheet') {
                    return new class($this->worksheet) {
                        public function __construct(private $worksheet)
                        {
                        }

                        public function getDelegate()
                        {
                            return $this->worksheet;
                        }
                    };
                }

                return null;
            }
        });

        $this->assertContains('A1:B1', array_values($worksheet->getMergeCells()));
        $this->assertSame('A3', $worksheet->getFreezePane());
        $this->assertTrue($worksheet->getColumnDimension('A')->getAutoSize());
        $this->assertTrue($worksheet->getColumnDimension('B')->getAutoSize());
        $this->assertSame('FEF3C7', $worksheet->getStyle('A3')->getFill()->getStartColor()->getRGB());
    }
}