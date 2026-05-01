<?php

namespace Tests\Feature;

use App\Exports\LeavesAnomalyResolutionExport;
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
use Tests\TestCase;

class LeavesAnomalyResolutionExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-04-22 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_tombol_export_resolusi_tampil_di_dashboard(): void
    {
        [$admin] = $this->makeContextWithResolution();

        $response = $this->actingAs($admin)->get(route('leaves.anomalies'));

        $response->assertOk();
        $response->assertSee('data-testid="leave-anomaly-resolution-export-pdf"', false);
        $response->assertSee('data-testid="leave-anomaly-resolution-export-xlsx"', false);
        $response->assertSee('Unduh rekap resolusi anomali cuti untuk audit');
    }

    public function test_file_xlsx_terunduh_dengan_nama_benar_dan_summary_sesuai(): void
    {
        Excel::fake();
        [$admin, , $employeeUser, $tenant] = $this->makeContextWithResolution();

        $this->actingAs($employeeUser)
            ->get(route('leaves.anomalies.resolutions.export.xlsx', ['tenant_id' => $tenant->id]))
            ->assertForbidden();

        $response = $this->actingAs($admin)
            ->get(route('leaves.anomalies.resolutions.export.xlsx', ['tenant_id' => $tenant->id]));

        $response->assertOk();

        Excel::assertDownloaded('leaves_anomaly_resolutions_tenant-leave-anomaly-resolution-export_20260422.xlsx', function (LeavesAnomalyResolutionExport $export) {
            $rows = array_map('array_values', $export->collection()->toArray());

            $this->assertCount(4, $rows);
            $this->assertContains([
                'Employee Lonjakan',
                'Lonjakan',
                'Employee Lonjakan mengalami lonjakan cuti bulan April (7 hari vs rata-rata 2.0 hari).',
                'April 2026',
                'Manager Leave Anomaly Resolution Export',
                'Investigasi',
                'Sudah ditinjau dan perlu monitoring lanjutan untuk bulan depan.',
                '22 Apr 2026 10:00',
            ], $rows);
            $this->assertContains([
                'Employee Carry',
                'Carry-Over',
                'Employee Carry memiliki indikasi carry-over cuti 12 hari dari tahun lalu.',
                'April 2026',
                '-',
                '-',
                '-',
                '-',
            ], $rows);
            $this->assertContains([
                'Employee Pola',
                'Pola Berulang',
                'Employee Pola menunjukkan pola berulang: cuti di hari Jumat 5x berturut-turut.',
                'April 2026',
                '-',
                '-',
                '-',
                '-',
            ], $rows);

            $worksheet = $this->buildWorksheetFromExport($export);
            $this->assertSame('Ringkasan Resolusi', $worksheet->getCell('A6')->getValue());
            $this->assertSame('Jumlah Resolved', $worksheet->getCell('A7')->getValue());
            $this->assertSame(1, $worksheet->getCell('B7')->getValue());
            $this->assertSame('Jumlah Unresolved', $worksheet->getCell('C7')->getValue());
            $this->assertSame(2, $worksheet->getCell('D7')->getValue());

            return true;
        });
    }

    public function test_file_pdf_terunduh_dengan_nama_benar_dan_detail_sesuai(): void
    {
        [$admin, , , $tenant] = $this->makeContextWithResolution();

        $response = $this->actingAs($admin)
            ->get(route('leaves.anomalies.resolutions.export.pdf', ['tenant_id' => $tenant->id]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'attachment; filename="leaves_anomaly_resolutions_tenant-leave-anomaly-resolution-export_20260422.pdf"');

        $payload = app(LeavesAnomalyService::class)->buildResolutionExportPayload($admin, $tenant->id);
        $rendered = view('leaves.exports.anomaly-resolutions-pdf', $payload)->render();

        $this->assertSame(1, $payload['summary']['resolved']);
        $this->assertSame(2, $payload['summary']['unresolved']);
        $this->assertStringContainsString('Laporan Resolusi Anomali Cuti', $rendered);
        $this->assertStringContainsString('Section Summary', $rendered);
        $this->assertStringContainsString('Section Detail', $rendered);
        $this->assertStringContainsString('Manager Leave Anomaly Resolution Export', $rendered);
        $this->assertStringContainsString('Sudah ditinjau dan perlu monitoring lanjutan untuk bulan depan.', $rendered);
    }

    protected function makeContextWithResolution(): array
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Leave Anomaly Resolution Export',
            'slug' => 'tenant-leave-anomaly-resolution-export',
            'domain' => 'tenant-leave-anomaly-resolution-export.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Leave Anomaly Resolution Export',
            'email' => 'admin-leave-anomaly-resolution-export@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $manager = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Manager Leave Anomaly Resolution Export',
            'email' => 'manager-leave-anomaly-resolution-export@example.test',
            'password' => 'password',
            'role' => 'manager',
            'status' => 'active',
        ]);

        $employeeUser = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Employee Leave Anomaly Resolution Export',
            'email' => 'employee-leave-anomaly-resolution-export@example.test',
            'password' => 'password',
            'role' => 'employee',
            'status' => 'active',
        ]);

        $employeeSpike = Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $employeeUser->id,
            'employee_code' => 'LRE-001',
            'name' => 'Employee Lonjakan',
            'email' => 'employee-lonjakan-resolution-export@example.test',
            'status' => 'active',
        ]);

        $employeePattern = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'LRE-002',
            'name' => 'Employee Pola',
            'email' => 'employee-pola-resolution-export@example.test',
            'status' => 'active',
        ]);

        $employeeCarry = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'LRE-003',
            'name' => 'Employee Carry',
            'email' => 'employee-carry-resolution-export@example.test',
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

        $this->makeLeave($tenant, $employeeCarry, '2025-01-05', '2025-01-10', 'approved', 'Carry 2025');
        $this->makeLeave($tenant, $employeeCarry, '2026-01-12', '2026-01-23', 'approved', 'Carry 2026');

        $this->actingAs($manager)->get(route('leaves.anomalies'))->assertOk();

        $notification = $manager->fresh()->notifications
            ->first(fn ($item) => data_get($item->data, 'employee_name') === 'Employee Lonjakan' && data_get($item->data, 'category') === 'leave_anomaly');

        $this->actingAs($manager)->post(route('leaves.anomalies.resolve', $notification->id), [
            'resolution_note' => 'Sudah ditinjau dan perlu monitoring lanjutan untuk bulan depan.',
            'resolution_action' => 'Investigasi',
        ])->assertRedirect();

        return [$admin, $manager, $employeeUser, $tenant];
    }

    protected function buildWorksheetFromExport(LeavesAnomalyResolutionExport $export)
    {
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

        return $worksheet;
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