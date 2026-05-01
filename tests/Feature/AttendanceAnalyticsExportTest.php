<?php

namespace Tests\Feature;

use App\Exports\AttendanceAnalyticsExport;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkLocation;
use App\Support\AttendanceAnalyticsReportBuilder;
use App\Support\AttendanceAnalyticsSvgBuilder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class AttendanceAnalyticsExportTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $admin;

    private User $manager;

    private User $employeeUser;

    private WorkLocation $workLocation;

    private array $employees = [];

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-04-21 10:00:00');

        $this->tenant = Tenant::create([
            'name' => 'Attendance Analytics Export Tenant',
            'slug' => 'attendance-analytics-export-tenant',
            'domain' => 'attendance-analytics-export-tenant.test',
            'status' => 'active',
        ]);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Attendance Analytics Export Admin',
            'email' => 'attendance-analytics-export-admin@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $this->manager = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Attendance Analytics Export Manager',
            'email' => 'attendance-analytics-export-manager@example.test',
            'password' => 'password',
            'role' => 'manager',
            'status' => 'active',
        ]);

        $this->employeeUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Attendance Analytics Export Employee',
            'email' => 'attendance-analytics-export-employee@example.test',
            'password' => 'password',
            'role' => 'employee',
            'status' => 'active',
        ]);

        $this->workLocation = WorkLocation::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Attendance Analytics Export Office',
            'address' => 'Jakarta',
            'latitude' => -6.2000000,
            'longitude' => 106.8166667,
            'radius' => 250,
        ]);

        $this->employees = collect(range(1, 5))->map(function (int $sequence) {
            return Employee::create([
                'tenant_id' => $this->tenant->id,
                'work_location_id' => $this->workLocation->id,
                'employee_code' => 'ATAX-00'.$sequence,
                'name' => 'Attendance Analytics Export Employee '.$sequence,
                'email' => 'attendance-analytics-export-employee-'.$sequence.'@example.test',
                'status' => 'active',
                'user_id' => $sequence === 1 ? $this->employeeUser->id : null,
            ]);
        })->all();

        Attendance::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $this->employees[0]->id,
            'date' => '2026-04-01',
            'check_in' => '08:00',
            'check_out' => '17:00',
            'status' => 'present',
        ]);

        Attendance::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $this->employees[1]->id,
            'date' => '2026-04-02',
            'check_in' => '08:30',
            'check_out' => '17:30',
            'status' => 'late',
        ]);

        Attendance::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $this->employees[2]->id,
            'date' => '2026-04-03',
            'status' => 'leave',
        ]);

        Attendance::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $this->employees[3]->id,
            'date' => '2026-04-04',
            'status' => 'sick',
        ]);

        Attendance::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $this->employees[4]->id,
            'date' => '2026-04-05',
            'status' => 'absent',
        ]);

        Attendance::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $this->employees[0]->id,
            'date' => '2026-01-15',
            'check_in' => '08:00',
            'check_out' => '16:00',
            'status' => 'present',
        ]);

        Attendance::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $this->employees[1]->id,
            'date' => '2025-12-11',
            'status' => 'leave',
        ]);

        Attendance::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $this->employees[2]->id,
            'date' => '2025-11-20',
            'status' => 'sick',
        ]);

        Attendance::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $this->employees[3]->id,
            'date' => '2025-07-20',
            'status' => 'absent',
        ]);

        Attendance::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $this->employees[4]->id,
            'date' => '2022-03-15',
            'check_in' => '09:00',
            'check_out' => '17:00',
            'status' => 'present',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_halaman_analytics_memiliki_tombol_export_pdf_dan_xlsx(): void
    {
        $response = $this->actingAs($this->admin)->get(route('attendances.analytics', [
            'year' => 2026,
            'month' => 4,
        ]));

        $response->assertOk();
        $response->assertSee('data-testid="attendance-analytics-export-pdf"', false);
        $response->assertSee('data-testid="attendance-analytics-export-xlsx"', false);
        $response->assertSee('Unduh laporan analitik absensi bulanan/tahunan');
    }

    public function test_file_xlsx_punya_dua_sheet_tabel_dan_chart(): void
    {
        Excel::fake();

        $response = $this->actingAs($this->admin)->get(route('attendances.analytics.export.xlsx', [
            'year' => 2026,
            'month' => 4,
        ]));

        $response->assertOk();

        Excel::assertDownloaded('attendance_analytics_attendance-analytics-export-tenant_2026_20260421.xlsx', function (AttendanceAnalyticsExport $export) {
            $sheets = $export->sheets();

            $this->assertCount(2, $sheets);
            $this->assertSame('Summary Bulanan', $sheets[0]->title());
            $this->assertSame('Summary Tahunan', $sheets[1]->title());

            $monthlyRows = $sheets[0]->collection()->values();
            $yearlyRows = $sheets[1]->collection()->values();

            $this->assertSame(['Tahun', 'Bulan', 'Hadir', 'Izin', 'Sakit', 'Alpha', 'Total Jam Kerja'], $monthlyRows[0]);
            $this->assertSame([2026, 'Apr', 2, 1, 1, 1, '18 jam 00 menit'], $monthlyRows[12]);
            $this->assertSame(['Tahun', 'Hadir', 'Izin', 'Sakit', 'Alpha', 'Total Jam Kerja'], $yearlyRows[0]);
            $this->assertSame([2026, 3, 1, 1, 1, '26 jam 00 menit'], $yearlyRows[5]);
            $this->assertCount(1, $sheets[0]->charts());
            $this->assertCount(1, $sheets[1]->charts());

            $spreadsheet = new Spreadsheet();
            $worksheet = $spreadsheet->getActiveSheet();

            foreach ($sheets[0]->collection()->values() as $rowIndex => $row) {
                foreach (array_values($row) as $columnIndex => $value) {
                    $worksheet->setCellValueByColumnAndRow($columnIndex + 1, $rowIndex + 1, $value);
                }
            }

            $events = $sheets[0]->registerEvents();
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

            $this->assertSame('A2', $worksheet->getFreezePane());
            $this->assertTrue($worksheet->getColumnDimension('G')->getAutoSize());

            return true;
        });
    }

    public function test_file_pdf_punya_section_summary_dan_grafik(): void
    {
        $response = $this->actingAs($this->admin)->get(route('attendances.analytics.export.pdf', [
            'year' => 2026,
            'month' => 4,
        ]));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('attendance_analytics_attendance-analytics-export-tenant_2026_20260421.pdf', (string) $response->headers->get('content-disposition'));

        $report = app(AttendanceAnalyticsReportBuilder::class)->build($this->admin, 2026, 4);
        $svgBuilder = app(AttendanceAnalyticsSvgBuilder::class);
        $html = view('attendances.exports.analytics-pdf', [
            ...$report,
            'monthlyTrendSvg' => $svgBuilder->buildLineChart($report['monthlyTrendChart'], $report['statusMeta']),
            'yearlyDistributionSvg' => $svgBuilder->buildBarChart($report['yearlyDistributionChart']),
            'statusDistributionSvg' => $svgBuilder->buildPieChart($report['statusDistributionChart']),
        ])->render();

        $this->assertStringContainsString('Laporan Analitik Absensi', $html);
        $this->assertStringContainsString('Section 1: Summary Bulanan', $html);
        $this->assertStringContainsString('Section 2: Summary Tahunan', $html);
        $this->assertStringContainsString('Section 3: Pie Chart Distribusi Status Bulan Berjalan', $html);
        $this->assertStringContainsString('18 jam 00 menit', $html);
        $this->assertStringContainsString('26 jam 00 menit', $html);
        $this->assertStringContainsString('<svg', $html);
    }

    public function test_manager_bisa_export_analytics(): void
    {
        Excel::fake();

        $response = $this->actingAs($this->manager)->get(route('attendances.analytics.export.xlsx', [
            'year' => 2026,
            'month' => 4,
        ]));

        $response->assertOk();

        Excel::assertDownloaded('attendance_analytics_attendance-analytics-export-tenant_2026_20260421.xlsx');
    }

    public function test_employee_tidak_bisa_export_analytics_pdf_dan_xlsx(): void
    {
        $pdfResponse = $this->actingAs($this->employeeUser)->get(route('attendances.analytics.export.pdf', [
            'year' => 2026,
            'month' => 4,
        ]));

        $xlsxResponse = $this->actingAs($this->employeeUser)->get(route('attendances.analytics.export.xlsx', [
            'year' => 2026,
            'month' => 4,
        ]));

        $pdfResponse->assertForbidden();
        $xlsxResponse->assertForbidden();
    }
}