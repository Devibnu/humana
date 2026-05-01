<?php

namespace Tests\Feature;

use App\Exports\PositionsExport;
use App\Exports\PositionsCsvExport;
use App\Exports\PositionsDataSheetExport;
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
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Tests\TestCase;

class PositionsExportStatusSummaryTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $admin;
    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Tenant Status Export Posisi',
            'code' => 'TEN-STAT-POS',
            'slug' => 'tenant-status-export-posisi',
            'domain' => 'tenant-status-export-posisi.test',
            'status' => 'active',
        ]);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin Status Export Posisi',
            'email' => 'admin-status-export-posisi@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $this->department = Department::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Operasional Audit',
            'code' => 'OPA',
            'description' => 'Departemen audit operasional.',
            'status' => 'active',
        ]);

        $supervisor = Position::create([
            'tenant_id' => $this->tenant->id,
            'department_id' => $this->department->id,
            'name' => 'Supervisor Audit',
            'code' => 'AUD-01',
            'description' => 'Memimpin audit lapangan.',
            'status' => 'active',
        ]);

        $staff = Position::create([
            'tenant_id' => $this->tenant->id,
            'department_id' => $this->department->id,
            'name' => 'Staf Audit',
            'code' => 'AUD-02',
            'description' => 'Mendukung audit harian.',
            'status' => 'inactive',
        ]);

        Position::create([
            'tenant_id' => $this->tenant->id,
            'department_id' => $this->department->id,
            'name' => 'Koordinator Audit',
            'code' => 'AUD-03',
            'description' => 'Koordinasi audit mingguan.',
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $this->tenant->id,
            'department_id' => $this->department->id,
            'position_id' => $supervisor->id,
            'employee_code' => 'EMP-AUD-01',
            'name' => 'Karyawan Audit Satu',
            'email' => 'karyawan-audit-satu@example.test',
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $this->tenant->id,
            'department_id' => $this->department->id,
            'position_id' => $staff->id,
            'employee_code' => 'EMP-AUD-02',
            'name' => 'Karyawan Audit Dua',
            'email' => 'karyawan-audit-dua@example.test',
            'status' => 'active',
        ]);
    }

    public function test_file_csv_memiliki_baris_awal_summary_status(): void
    {
        Carbon::setTestNow('2026-04-20 09:00:00');
        Excel::fake();

        $response = $this->actingAs($this->admin)
            ->get(route('departments.positions.export.csv', $this->department));

        $response->assertOk();

        Excel::assertDownloaded('positions_operasional_audit_20260420.csv', function (PositionsCsvExport $export) {
            $rows = $export->collection()->values();
            $dataRows = [$rows[3], $rows[4], $rows[5]];

            $this->assertSame('Total Posisi Aktif: 2', $rows[0][0]);
            $this->assertSame('Total Posisi Non-Aktif: 1', $rows[1][0]);
            $this->assertSame('Nama Posisi', $rows[2][0]);
            $this->assertSame('Status', $rows[2][3]);
            $this->assertSame('Jumlah Karyawan', $rows[2][4]);
            $this->assertSame(['Koordinator Audit', 'Staf Audit', 'Supervisor Audit'], collect($dataRows)->pluck(0)->sort()->values()->all());
            $this->assertContains('Aktif', collect($dataRows)->pluck(3)->all());
            $this->assertContains('Non-Aktif', collect($dataRows)->pluck(3)->all());

            return true;
        });
    }

    public function test_file_xlsx_memiliki_header_summary_status_dan_freeze_row_enam(): void
    {
        $sheet = new PositionsDataSheetExport(collect([
            (object) [
                'name' => 'Koordinator Audit',
                'code' => 'AUD-03',
                'description' => 'Koordinasi audit mingguan.',
                'status' => 'active',
                'employees_count' => 0,
            ],
            (object) [
                'name' => 'Staf Audit',
                'code' => 'AUD-02',
                'description' => 'Mendukung audit harian.',
                'status' => 'inactive',
                'employees_count' => 1,
            ],
            (object) [
                'name' => 'Supervisor Audit',
                'code' => 'AUD-01',
                'description' => 'Memimpin audit lapangan.',
                'status' => 'active',
                'employees_count' => 1,
            ],
        ]), [
            'department_name' => $this->department->name,
            'active_count' => 2,
            'inactive_count' => 1,
            'format' => 'xlsx',
        ]);

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

        $this->assertContains('A1:E1', array_values($worksheet->getMergeCells()));
        $this->assertContains('A2:E2', array_values($worksheet->getMergeCells()));
        $this->assertContains('A3:E3', array_values($worksheet->getMergeCells()));
        $this->assertSame('Summary Export Departemen', $worksheet->getCell('A1')->getValue());
        $this->assertSame('Total Posisi Aktif: 2', $worksheet->getCell('A2')->getValue());
        $this->assertSame('Total Posisi Non-Aktif: 1', $worksheet->getCell('A3')->getValue());
        $this->assertSame('Nama Posisi', $worksheet->getCell('A5')->getValue());
        $this->assertSame('Status', $worksheet->getCell('D5')->getValue());
        $this->assertSame('A6', $worksheet->getFreezePane());
        $this->assertSame(Alignment::VERTICAL_CENTER, $worksheet->getStyle('A2')->getAlignment()->getVertical());
        $this->assertSame(Alignment::VERTICAL_CENTER, $worksheet->getStyle('A3')->getAlignment()->getVertical());
        $this->assertTrue($worksheet->getColumnDimension('A')->getAutoSize());
        $this->assertTrue($worksheet->getColumnDimension('E')->getAutoSize());
        $this->assertSame('FFF7ED', $worksheet->getStyle('A6')->getFill()->getStartColor()->getRGB());
    }
}