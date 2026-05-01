<?php

namespace Tests\Feature;

use App\Exports\LeavesEmployeeAggregationExport;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class LeavesEmployeeAggregationExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_file_xlsx_punya_tiga_sheet(): void
    {
        Carbon::setTestNow('2026-04-21 10:00:00');
        Excel::fake();

        [$admin, $employee] = $this->makeContext();

        $response = $this->actingAs($admin)->get(route('leaves.employee.export.aggregation.xlsx', $employee));

        $response->assertOk();

        Excel::assertDownloaded('leaves_rani_agregasi_cuti_20260421_aggregation.xlsx', function (LeavesEmployeeAggregationExport $export) {
            $sheets = $export->sheets();

            $this->assertCount(3, $sheets);
            $this->assertSame('Detail Harian', $sheets[0]->title());
            $this->assertSame('Rekap Bulanan', $sheets[1]->title());
            $this->assertSame('Rekap Tahunan', $sheets[2]->title());

            return true;
        });
    }

    public function test_sheet_bulanan_dan_tahunan_berisi_agregasi_sesuai_data(): void
    {
        Carbon::setTestNow('2026-04-21 10:00:00');
        Excel::fake();

        [$admin, $employee] = $this->makeContext();

        $response = $this->actingAs($admin)->get(route('leaves.employee.export.aggregation.xlsx', $employee));

        $response->assertOk();

        Excel::assertDownloaded('leaves_rani_agregasi_cuti_20260421_aggregation.xlsx', function (LeavesEmployeeAggregationExport $export) {
            $sheets = $export->sheets();
            $detailRows = array_map('array_values', $sheets[0]->collection()->toArray());
            $monthlyRows = $sheets[1]->collection()->toArray();
            $annualRows = $sheets[2]->collection()->toArray();

            $this->assertCount(4, $detailRows);
            $this->assertContains(['Cuti Tahunan', '2026-01-10', '2026-01-12', 3, 'Pending', 'Pending Januari'], $detailRows);
            $this->assertContains(['Izin', '2026-02-05', '2026-02-05', 1, 'Approved', 'Approved Februari'], $detailRows);
            $this->assertContains(['Sakit', '2026-02-07', '2026-02-09', 3, 'Rejected', 'Rejected Februari'], $detailRows);
            $this->assertContains(['Cuti Tahunan', '2025-12-20', '2025-12-21', 2, 'Approved', 'Approved Desember'], $detailRows);

            $this->assertContains([2025, 'December', 0, 1, 0, 2], $monthlyRows);
            $this->assertContains([2026, 'January', 1, 0, 0, 3], $monthlyRows);
            $this->assertContains([2026, 'February', 0, 1, 1, 4], $monthlyRows);

            $this->assertContains([2025, 0, 1, 0, 2], $annualRows);
            $this->assertContains([2026, 1, 1, 1, 7], $annualRows);

            return true;
        });
    }

    public function test_sheet_rekap_punya_summary_header_merged(): void
    {
        Carbon::setTestNow('2026-04-21 10:00:00');
        Excel::fake();

        [$admin, $employee] = $this->makeContext();

        $response = $this->actingAs($admin)->get(route('leaves.employee.export.aggregation.xlsx', $employee));

        $response->assertOk();

        Excel::assertDownloaded('leaves_rani_agregasi_cuti_'.now()->format('Ymd').'_aggregation.xlsx', function (LeavesEmployeeAggregationExport $export) {
            $monthlySheet = $export->sheets()[1];
            $yearlySheet = $export->sheets()[2];

            $monthlyWorksheet = $this->buildWorksheetFromSheet($monthlySheet);
            $yearlyWorksheet = $this->buildWorksheetFromSheet($yearlySheet);

            $this->assertContains('A1:F1', array_values($monthlyWorksheet->getMergeCells()));
            $this->assertSame('Summary Rekap Cuti Bulanan/Tahunan', $monthlyWorksheet->getCell('A1')->getValue());
            $this->assertSame('Tahun', $monthlyWorksheet->getCell('A3')->getValue());
            $this->assertSame('Bulan', $monthlyWorksheet->getCell('B3')->getValue());
            $this->assertSame('A4', $monthlyWorksheet->getFreezePane());

            $this->assertContains('A1:E1', array_values($yearlyWorksheet->getMergeCells()));
            $this->assertSame('Summary Rekap Cuti Bulanan/Tahunan', $yearlyWorksheet->getCell('A1')->getValue());
            $this->assertSame('Tahun', $yearlyWorksheet->getCell('A3')->getValue());
            $this->assertSame('Total Hari Cuti', $yearlyWorksheet->getCell('E3')->getValue());
            $this->assertSame('A4', $yearlyWorksheet->getFreezePane());

            return true;
        });
    }

    public function test_total_hari_cuti_dihitung_benar(): void
    {
        Carbon::setTestNow('2026-04-21 10:00:00');
        Excel::fake();

        [$admin, $employee] = $this->makeContext();

        $response = $this->actingAs($admin)->get(route('leaves.employee.export.aggregation.xlsx', $employee));

        $response->assertOk();

        Excel::assertDownloaded('leaves_rani_agregasi_cuti_20260421_aggregation.xlsx', function (LeavesEmployeeAggregationExport $export) {
            $annualRows = $export->sheets()[2]->collection()->toArray();

            $this->assertSame(2, $annualRows[0][4]);
            $this->assertSame(7, $annualRows[1][4]);

            return true;
        });
    }

    public function test_rbac_hanya_admin_dan_manager_bisa_export(): void
    {
        Carbon::setTestNow('2026-04-21 10:00:00');

        [$admin, $employee, $manager, $employeeUser, $otherTenantEmployee, $otherTenantManager] = $this->makeContext();

        $this->actingAs($admin)->get(route('leaves.employee.export.aggregation.xlsx', $employee))->assertOk();
        $this->actingAs($manager)->get(route('leaves.employee.export.aggregation.xlsx', $employee))->assertOk();
        $this->actingAs($employeeUser)->get(route('leaves.employee.export.aggregation.xlsx', $employee))->assertForbidden();
        $this->actingAs($otherTenantManager)->get(route('leaves.employee.export.aggregation.xlsx', $employee))->assertNotFound();
        $this->actingAs($manager)->get(route('leaves.employee.export.aggregation.xlsx', $otherTenantEmployee))->assertNotFound();
    }

    protected function makeContext(): array
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Leaves Aggregation Export',
            'slug' => 'tenant-leaves-aggregation-export',
            'domain' => 'tenant-leaves-aggregation-export.test',
            'status' => 'active',
        ]);

        $otherTenant = Tenant::create([
            'name' => 'Tenant Lain Aggregation Export',
            'slug' => 'tenant-lain-aggregation-export',
            'domain' => 'tenant-lain-aggregation-export.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Aggregation Export',
            'email' => 'admin-aggregation-export@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $manager = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Manager Aggregation Export',
            'email' => 'manager-aggregation-export@example.test',
            'password' => 'password',
            'role' => 'manager',
            'status' => 'active',
        ]);

        $employeeUser = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Employee Aggregation Export',
            'email' => 'employee-aggregation-export@example.test',
            'password' => 'password',
            'role' => 'employee',
            'status' => 'active',
        ]);

        $otherTenantManager = User::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Manager Other Tenant Aggregation Export',
            'email' => 'manager-other-aggregation-export@example.test',
            'password' => 'password',
            'role' => 'manager',
            'status' => 'active',
        ]);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $employeeUser->id,
            'employee_code' => 'LAG-001',
            'name' => 'Rani Agregasi Cuti',
            'email' => 'rani-agregasi-cuti@example.test',
            'status' => 'active',
        ]);

        $otherTenantEmployee = Employee::create([
            'tenant_id' => $otherTenant->id,
            'employee_code' => 'LAG-002',
            'name' => 'Dina Tenant Lain',
            'email' => 'dina-tenant-lain@example.test',
            'status' => 'active',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => '2026-01-10',
            'end_date' => '2026-01-12',
            'reason' => 'Pending Januari',
            'status' => 'pending',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'permission',
            'start_date' => '2026-02-05',
            'end_date' => '2026-02-05',
            'reason' => 'Approved Februari',
            'status' => 'approved',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'sick',
            'start_date' => '2026-02-07',
            'end_date' => '2026-02-09',
            'reason' => 'Rejected Februari',
            'status' => 'rejected',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => '2025-12-20',
            'end_date' => '2025-12-21',
            'reason' => 'Approved Desember',
            'status' => 'approved',
        ]);

        Leave::create([
            'tenant_id' => $otherTenant->id,
            'employee_id' => $otherTenantEmployee->id,
            'leave_type' => 'annual',
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-02',
            'reason' => 'Other tenant leave',
            'status' => 'approved',
        ]);

        return [$admin, $employee, $manager, $employeeUser, $otherTenantEmployee, $otherTenantManager];
    }

    protected function buildWorksheetFromSheet(object $sheetExport)
    {
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        foreach ($sheetExport->headings() as $columnIndex => $value) {
            $worksheet->setCellValueByColumnAndRow($columnIndex + 1, 1, $value);
        }

        foreach ($sheetExport->collection()->values() as $rowIndex => $row) {
            foreach (array_values($row) as $columnIndex => $value) {
                $worksheet->setCellValueByColumnAndRow($columnIndex + 1, $rowIndex + 2, $value);
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
}