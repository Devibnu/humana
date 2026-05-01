<?php

namespace Tests\Feature;

use App\Exports\AttendanceEmployeeCsvExport;
use App\Exports\AttendanceEmployeeExport;
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

class AttendanceEmployeeExportTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $admin;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Tenant Attendance Employee Export',
            'slug' => 'tenant-attendance-employee-export',
            'domain' => 'tenant-attendance-employee-export.test',
            'status' => 'active',
        ]);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin Attendance Employee Export',
            'email' => 'admin-attendance-employee-export@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $workLocation = WorkLocation::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Kantor Pusat Audit',
            'address' => 'Jakarta',
            'latitude' => -6.2000000,
            'longitude' => 106.8166667,
            'radius' => 250,
        ]);

        $this->employee = Employee::create([
            'tenant_id' => $this->tenant->id,
            'work_location_id' => $workLocation->id,
            'employee_code' => 'AEE-001',
            'name' => 'Budi Audit',
            'email' => 'budi-audit@example.test',
            'status' => 'active',
        ]);

        Attendance::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $this->employee->id,
            'date' => '2026-04-18',
            'check_in' => '08:00',
            'check_out' => '17:00',
            'status' => 'present',
        ]);

        Attendance::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $this->employee->id,
            'date' => '2026-04-19',
            'check_in' => '08:15',
            'check_out' => '17:15',
            'status' => 'leave',
        ]);

        Attendance::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $this->employee->id,
            'date' => '2026-04-20',
            'check_in' => '08:30',
            'check_out' => '12:30',
            'status' => 'sick',
        ]);

        Attendance::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $this->employee->id,
            'date' => '2026-04-21',
            'status' => 'absent',
        ]);
    }

    public function test_employee_show_tab_attendance_menampilkan_tombol_export(): void
    {
        $response = $this->actingAs($this->admin)->get(route('employees.show', $this->employee));

        $response->assertOk();
        $response->assertSee('data-testid="tab-attendance"', false);
        $response->assertSee('Riwayat Absensi');
        $response->assertSee('data-testid="btn-export-employee-attendance-csv"', false);
        $response->assertSee('data-testid="btn-export-employee-attendance-xlsx"', false);
        $response->assertSee('Unduh data absensi individu untuk audit operasional');
        $response->assertSee('data-testid="attendance-table"', false);
    }

    public function test_file_csv_punya_baris_awal_summary_dan_total_jam_kerja(): void
    {
        Carbon::setTestNow('2026-04-21 10:00:00');
        Excel::fake();

        $response = $this->actingAs($this->admin)->get(route('attendances.employee.export.csv', $this->employee));

        $response->assertOk();

        Excel::assertDownloaded('attendance_budi_audit_20260421.csv', function (AttendanceEmployeeCsvExport $export) {
            $rows = $export->collection()->values();

            $this->assertSame('Total Hadir: 1', $rows[0][0]);
            $this->assertSame('Total Izin: 1', $rows[1][0]);
            $this->assertSame('Total Sakit: 1', $rows[2][0]);
            $this->assertSame('Total Alpha: 1', $rows[3][0]);
            $this->assertSame('Total Jam Kerja: 22 jam 00 menit', $rows[4][0]);
            $this->assertSame('Tanggal', $rows[5][0]);
            $this->assertSame('Durasi Jam Kerja', $rows[5][4]);

            return true;
        });
    }

    public function test_file_xlsx_punya_header_summary_row_dua_sampai_enam_dan_total_jam_kerja_benar(): void
    {
        $export = new AttendanceEmployeeExport(
            $this->employee,
            Attendance::query()
                ->with(['employee', 'employee.workLocation', 'attendanceLog.workLocation'])
                ->where('employee_id', $this->employee->id)
                ->orderBy('date')
                ->get(),
            [
                'present_count' => 1,
                'leave_count' => 1,
                'sick_count' => 1,
                'absent_count' => 1,
            ]
        );

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
        $this->assertSame('Summary Absensi Budi Audit', $worksheet->getCell('A1')->getValue());
        $this->assertSame('Total Hadir: 1', $worksheet->getCell('A2')->getValue());
        $this->assertSame('Total Izin: 1', $worksheet->getCell('A3')->getValue());
        $this->assertSame('Total Sakit: 1', $worksheet->getCell('A4')->getValue());
        $this->assertSame('Total Alpha: 1', $worksheet->getCell('A5')->getValue());
        $this->assertSame('Total Jam Kerja: 22 jam 00 menit', $worksheet->getCell('A6')->getValue());
        $this->assertSame('Tanggal', $worksheet->getCell('A8')->getValue());
        $this->assertSame('Lokasi', $worksheet->getCell('F8')->getValue());
        $this->assertSame('A8', $worksheet->getFreezePane());
        $this->assertSame(Alignment::VERTICAL_CENTER, $worksheet->getStyle('A2')->getAlignment()->getVertical());
        $this->assertSame(Alignment::VERTICAL_CENTER, $worksheet->getStyle('A6')->getAlignment()->getVertical());
        $this->assertTrue($worksheet->getColumnDimension('A')->getAutoSize());
        $this->assertTrue($worksheet->getColumnDimension('F')->getAutoSize());
        $this->assertSame('F9FAFB', $worksheet->getStyle('A9')->getFill()->getStartColor()->getRGB());
    }
}