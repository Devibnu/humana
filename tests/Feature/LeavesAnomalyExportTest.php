<?php

namespace Tests\Feature;

use App\Exports\LeavesAnomalyExport;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Tenant;
use App\Models\User;
use App\Services\LeavesAnomalyService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Tests\TestCase;

class LeavesAnomalyExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-04-21 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_tombol_export_tampil_di_dashboard(): void
    {
        [$admin] = $this->makeContext();

        $response = $this->actingAs($admin)->get(route('leaves.anomalies'));

        $response->assertOk();
        $response->assertSee('data-testid="leave-anomaly-export-pdf"', false);
        $response->assertSee('data-testid="leave-anomaly-export-xlsx"', false);
        $response->assertSee('Unduh laporan anomali cuti untuk audit');
    }

    public function test_file_xlsx_terunduh_dengan_nama_benar_dan_data_sesuai(): void
    {
        Excel::fake();
        [$admin, , , $tenant] = $this->makeContext();

        $response = $this->actingAs($admin)->get(route('leaves.anomalies.export.xlsx', ['tenant_id' => $tenant->id]));

        $response->assertOk();

        Excel::assertDownloaded('leaves_anomalies_tenant-leave-anomaly-export_20260421.xlsx', function (LeavesAnomalyExport $export) {
            $sheets = $export->sheets();
            $summaryRows = array_map('array_values', $sheets[0]->collection()->toArray());
            $detailRows = array_map('array_values', $sheets[1]->collection()->toArray());

            $this->assertCount(4, $summaryRows);
            $this->assertSame(['Lonjakan', 1, 'Employee Lonjakan mengalami lonjakan cuti bulan April (7 hari vs rata-rata 2.0 hari).'], $summaryRows[1]);
            $this->assertSame(['Pola Berulang', 1, 'Employee Pola menunjukkan pola berulang: cuti di hari Jumat 5x berturut-turut.'], $summaryRows[2]);
            $this->assertSame(['Carry-Over', 1, 'Employee Carry memiliki indikasi carry-over cuti 12 hari dari tahun lalu.'], $summaryRows[3]);

            $this->assertCount(4, $detailRows);
            $this->assertSame(['Employee Lonjakan', 'Lonjakan', 'Employee Lonjakan mengalami lonjakan cuti bulan April (7 hari vs rata-rata 2.0 hari).', 'April 2026', 'Belum Diselesaikan', '-', '-', '-'], $detailRows[1]);
            $this->assertSame(['Employee Pola', 'Pola Berulang', 'Employee Pola menunjukkan pola berulang: cuti di hari Jumat 5x berturut-turut.', 'April 2026', 'Belum Diselesaikan', '-', '-', '-'], $detailRows[2]);
            $this->assertSame(['Employee Carry', 'Carry-Over', 'Employee Carry memiliki indikasi carry-over cuti 12 hari dari tahun lalu.', 'April 2026', 'Belum Diselesaikan', '-', '-', '-'], $detailRows[3]);

            return true;
        });
    }

    public function test_file_pdf_terunduh_dengan_nama_benar_dan_payload_sesuai(): void
    {
        [$admin, , , $tenant] = $this->makeContext();

        $response = $this->actingAs($admin)->get(route('leaves.anomalies.export.pdf', ['tenant_id' => $tenant->id]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'attachment; filename="leaves_anomalies_tenant-leave-anomaly-export_20260421.pdf"');

        $service = app(LeavesAnomalyService::class);
        $payload = $service->buildExportPayload($admin, $tenant->id);
        $rendered = view('leaves.exports.anomalies-pdf', $payload)->render();

        $this->assertSame('Tenant Leave Anomaly Export', $payload['tenant']->name);
        $this->assertSame(1, $payload['summary']['spike_count']);
        $this->assertSame(1, $payload['summary']['recurring_count']);
        $this->assertSame(1, $payload['summary']['carry_over_count']);
        $this->assertSame('Employee Lonjakan', $payload['detailRows'][0]['employee']);
        $this->assertSame('Carry-Over', $payload['detailRows'][2]['jenis_anomali']);
        $this->assertStringContainsString('Laporan Anomali Cuti', $rendered);
        $this->assertStringContainsString('Section Summary', $rendered);
        $this->assertStringContainsString('Section Detail', $rendered);
        $this->assertStringContainsString('Employee Pola menunjukkan pola berulang: cuti di hari Jumat 5x berturut-turut.', $rendered);
    }

    public function test_warna_status_konsisten_di_file_xlsx(): void
    {
        [$admin] = $this->makeContext();
        $service = app(LeavesAnomalyService::class);
        $payload = $service->buildExportPayload($admin);
        $export = new LeavesAnomalyExport($payload);

        $summaryWorksheet = $this->buildWorksheetFromSheet($export->sheets()[0]);
        $detailWorksheet = $this->buildWorksheetFromSheet($export->sheets()[1]);

        $this->assertSame('F9FAFB', $summaryWorksheet->getStyle('A2')->getFill()->getStartColor()->getRGB());
        $this->assertSame(Fill::FILL_SOLID, $detailWorksheet->getStyle('A2')->getFill()->getFillType());
        $this->assertSame('FEE2E2', $detailWorksheet->getStyle('A2')->getFill()->getStartColor()->getRGB());
        $this->assertSame('FFEDD5', $detailWorksheet->getStyle('A3')->getFill()->getStartColor()->getRGB());
        $this->assertSame('DBEAFE', $detailWorksheet->getStyle('A4')->getFill()->getStartColor()->getRGB());
        $this->assertSame('A2', $detailWorksheet->getFreezePane());
        $this->assertStringContainsString('Employee Lonjakan mengalami lonjakan cuti bulan April', $detailWorksheet->getComment('C2')->getText()->getPlainText());
    }

    protected function buildWorksheetFromSheet(object $sheetExport)
    {
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        foreach ($sheetExport->collection()->values() as $rowIndex => $row) {
            foreach (array_values($row) as $columnIndex => $value) {
                $worksheet->setCellValueByColumnAndRow($columnIndex + 1, $rowIndex + 1, $value);
            }
        }

        $events = $sheetExport->registerEvents();
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

        return $worksheet;
    }

    protected function makeContext(): array
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Leave Anomaly Export',
            'slug' => 'tenant-leave-anomaly-export',
            'domain' => 'tenant-leave-anomaly-export.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Leave Anomaly Export',
            'email' => 'admin-leave-anomaly-export@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $manager = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Manager Leave Anomaly Export',
            'email' => 'manager-leave-anomaly-export@example.test',
            'password' => 'password',
            'role' => 'manager',
            'status' => 'active',
        ]);

        $employeeUser = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Employee Leave Anomaly Export',
            'email' => 'employee-leave-anomaly-export@example.test',
            'password' => 'password',
            'role' => 'employee',
            'status' => 'active',
        ]);

        $employeeSpike = Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $employeeUser->id,
            'employee_code' => 'LAX-001',
            'name' => 'Employee Lonjakan',
            'email' => 'employee-lonjakan-export@example.test',
            'status' => 'active',
        ]);

        $employeePattern = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'LAX-002',
            'name' => 'Employee Pola',
            'email' => 'employee-pola-export@example.test',
            'status' => 'active',
        ]);

        $employeeCarry = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'LAX-003',
            'name' => 'Employee Carry',
            'email' => 'employee-carry-export@example.test',
            'status' => 'active',
        ]);

        $this->makeLeave($tenant, $employeeSpike, '2026-01-06', '2026-01-07', 'approved', 'Baseline Januari');
        $this->makeLeave($tenant, $employeeSpike, '2026-02-10', '2026-02-11', 'approved', 'Baseline Februari');
        $this->makeLeave($tenant, $employeeSpike, '2026-03-03', '2026-03-04', 'approved', 'Baseline Maret');
        $this->makeLeave($tenant, $employeeSpike, '2026-04-01', '2026-04-07', 'approved', 'Lonjakan April');

        $this->makeLeave($tenant, $employeePattern, '2026-01-02', '2026-01-02', 'pending', 'Jumat 1');
        $this->makeLeave($tenant, $employeePattern, '2026-02-06', '2026-02-06', 'pending', 'Jumat 2');
        $this->makeLeave($tenant, $employeePattern, '2026-03-06', '2026-03-06', 'pending', 'Jumat 3');
        $this->makeLeave($tenant, $employeePattern, '2026-04-03', '2026-04-03', 'pending', 'Jumat 4');
        $this->makeLeave($tenant, $employeePattern, '2026-05-01', '2026-05-01', 'pending', 'Jumat 5');

        $this->makeLeave($tenant, $employeeCarry, '2026-01-12', '2026-01-23', 'approved', 'Carry 2026');

        return [$admin, $manager, $employeeUser, $tenant];
    }

    protected function makeLeave(Tenant $tenant, Employee $employee, string $startDate, string $endDate, string $status, string $reason): Leave
    {
        return Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => $reason,
            'status' => $status,
        ]);
    }
}