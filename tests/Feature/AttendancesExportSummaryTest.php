<?php

namespace Tests\Feature;

use App\Exports\AttendancesCsvExport;
use App\Exports\AttendancesExport;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkLocation;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Tests\TestCase;

class AttendancesExportSummaryTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $admin;

    private WorkLocation $workLocation;

    private array $employees = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Tenant Export Attendance',
            'slug' => 'tenant-export-attendance',
            'domain' => 'tenant-export-attendance.test',
            'status' => 'active',
        ]);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin Export Attendance',
            'email' => 'admin-export-attendance@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $this->workLocation = WorkLocation::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Kantor Audit Operasional',
            'address' => 'Jakarta',
            'latitude' => -6.2000000,
            'longitude' => 106.8166667,
            'radius' => 250,
        ]);

        $this->employees = collect(range(1, 5))->map(function (int $sequence) {
            return Employee::create([
                'tenant_id' => $this->tenant->id,
                'work_location_id' => $this->workLocation->id,
                'employee_code' => 'ATE-00'.$sequence,
                'name' => 'Karyawan Export '.$sequence,
                'email' => 'karyawan-export-'.$sequence.'@example.test',
                'status' => 'active',
            ]);
        })->all();

        Attendance::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $this->employees[0]->id,
            'date' => '2026-04-21',
            'check_in' => '08:00',
            'check_out' => '17:00',
            'status' => 'present',
        ]);

        Attendance::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $this->employees[1]->id,
            'date' => '2026-04-21',
            'check_in' => '08:10',
            'check_out' => '17:10',
            'status' => 'late',
        ]);

        Attendance::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $this->employees[2]->id,
            'date' => '2026-04-21',
            'status' => 'leave',
        ]);

        Attendance::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $this->employees[3]->id,
            'date' => '2026-04-21',
            'status' => 'sick',
        ]);

        Attendance::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $this->employees[4]->id,
            'date' => '2026-04-21',
            'status' => 'absent',
        ]);
    }

    public function test_file_csv_memiliki_baris_awal_summary(): void
    {
        Carbon::setTestNow('2026-04-21 09:00:00');
        Excel::fake();

        $response = $this->actingAs($this->admin)->get(route('attendances.export.csv', [
            'start_date' => '2026-04-21',
            'end_date' => '2026-04-21',
        ]));

        $response->assertOk();

        Excel::assertDownloaded('attendances_20260421.csv', function (AttendancesCsvExport $export) {
            $rows = $export->collection()->values();

            $this->assertSame('Total Hadir: 2', $rows[0][0]);
            $this->assertSame('Total Izin: 1', $rows[1][0]);
            $this->assertSame('Total Sakit: 1', $rows[2][0]);
            $this->assertSame('Total Alpha: 1', $rows[3][0]);
            $this->assertSame('Nama Karyawan', $rows[4][0]);
            $this->assertSame('Tanggal', $rows[4][1]);
            $this->assertSame('Status', $rows[4][2]);
            $this->assertSame('Jam Keluar', $rows[4][4]);
            $this->assertSame('Lokasi', $rows[4][5]);

            return true;
        });
    }

    public function test_file_xlsx_punya_header_summary_row_dua_sampai_lima(): void
    {
        $export = new AttendancesExport(Attendance::query()
            ->with(['employee', 'employee.workLocation', 'attendanceLog.workLocation'])
            ->whereDate('date', '2026-04-21')
            ->orderBy('date')
            ->orderBy('id')
            ->get(), [
                'present_count' => 2,
                'leave_count' => 1,
                'sick_count' => 1,
                'absent_count' => 1,
            ]);

        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        foreach ($export->collection()->values() as $rowIndex => $row) {
            foreach (array_values($row) as $columnIndex => $value) {
                $worksheet->setCellValueByColumnAndRow($columnIndex + 1, $rowIndex + 1, $value);
            }
        }

        $events = $export->registerEvents();
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

        $this->assertContains('A1:D1', array_values($worksheet->getMergeCells()));
        $this->assertSame('Summary Absensi Harian', $worksheet->getCell('A1')->getValue());
        $this->assertSame('Total Hadir: 2', $worksheet->getCell('A2')->getValue());
        $this->assertSame('Total Izin: 1', $worksheet->getCell('A3')->getValue());
        $this->assertSame('Total Sakit: 1', $worksheet->getCell('A4')->getValue());
        $this->assertSame('Total Alpha: 1', $worksheet->getCell('A5')->getValue());
        $this->assertSame('Nama Karyawan', $worksheet->getCell('A7')->getValue());
        $this->assertSame('Lokasi', $worksheet->getCell('F7')->getValue());
        $this->assertSame('A7', $worksheet->getFreezePane());
        $this->assertSame(Alignment::VERTICAL_CENTER, $worksheet->getStyle('A2')->getAlignment()->getVertical());
        $this->assertSame(Alignment::VERTICAL_CENTER, $worksheet->getStyle('A5')->getAlignment()->getVertical());
        $this->assertTrue($worksheet->getColumnDimension('A')->getAutoSize());
        $this->assertTrue($worksheet->getColumnDimension('F')->getAutoSize());
        $this->assertSame('F9FAFB', $worksheet->getStyle('A8')->getFill()->getStartColor()->getRGB());
    }
}