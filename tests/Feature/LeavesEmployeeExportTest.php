<?php

namespace Tests\Feature;

use App\Exports\LeavesEmployeeCsvExport;
use App\Exports\LeavesEmployeeExport;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Tests\TestCase;

class LeavesEmployeeExportTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $admin;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $fakeFilePath = base_path('vendor/maatwebsite/excel/src/Fakes/fake_file');

        if (! file_exists($fakeFilePath)) {
            @mkdir(dirname($fakeFilePath), 0777, true);
            file_put_contents($fakeFilePath, 'fake');
        }

        $this->tenant = Tenant::create([
            'name' => 'Tenant Leaves Employee Export',
            'slug' => 'tenant-leaves-employee-export',
            'domain' => 'tenant-leaves-employee-export.test',
            'status' => 'active',
        ]);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin Leaves Employee Export',
            'email' => 'admin-leaves-employee-export@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $this->employee = Employee::create([
            'tenant_id' => $this->tenant->id,
            'employee_code' => 'LEE-001',
            'name' => 'Sinta Audit Cuti',
            'email' => 'sinta-audit-cuti@example.test',
            'status' => 'active',
        ]);

        Leave::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->resolveLeaveTypeId('annual'),
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-02',
            'reason' => 'Pending audit leave',
            'status' => 'pending',
        ]);

        Leave::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->resolveLeaveTypeId('permission'),
            'start_date' => '2026-04-05',
            'end_date' => '2026-04-07',
            'reason' => 'Approved audit leave',
            'status' => 'approved',
        ]);

        Leave::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->resolveLeaveTypeId('sick'),
            'start_date' => '2026-04-09',
            'end_date' => '2026-04-09',
            'reason' => 'Rejected audit leave',
            'status' => 'rejected',
        ]);
    }

    public function test_employee_show_tab_leaves_menampilkan_tombol_export(): void
    {
        $response = $this->actingAs($this->admin)->get(route('employees.show', $this->employee));

        $response->assertOk();
        $response->assertSee('data-testid="tab-leaves"', false);
        $response->assertSee('Riwayat Cuti');
        $response->assertSee('data-testid="btn-export-employee-leaves-csv"', false);
        $response->assertSee('data-testid="btn-export-employee-leaves-xlsx"', false);
        $response->assertSee('Unduh data cuti individu untuk audit operasional');
        $response->assertSee('data-testid="employee-leaves-table"', false);
    }

    public function test_file_csv_punya_baris_awal_summary_dan_total_hari_cuti_benar(): void
    {
        Carbon::setTestNow('2026-04-21 10:00:00');
        Excel::fake();

        $response = $this->actingAs($this->admin)->get(route('leaves.employee.export.csv', $this->employee));

        $response->assertOk();

        Excel::assertDownloaded('leaves_sinta_audit_cuti_20260421.csv', function (LeavesEmployeeCsvExport $export) {
            $rows = $export->collection()->values();

            $this->assertSame('Pending: 1 requests / 2 hari', $rows[0][0]);
            $this->assertSame('Approved: 1 requests / 3 hari', $rows[1][0]);
            $this->assertSame('Rejected: 1 requests / 1 hari', $rows[2][0]);
            $this->assertSame('Total Hari Cuti: 6', $rows[3][0]);
            $this->assertSame('Jenis Cuti', $rows[4][0]);
            $this->assertSame('Alasan', $rows[4][5]);

            return true;
        });
    }

    public function test_file_xlsx_punya_header_summary_row_dua_sampai_lima_dan_total_hari_cuti_benar(): void
    {
        $export = new LeavesEmployeeExport(
            $this->employee,
            Leave::query()->where('employee_id', $this->employee->id)->orderBy('start_date')->get(),
            [
                'pending_count' => 1,
                'pending_days' => 2,
                'approved_count' => 1,
                'approved_days' => 3,
                'rejected_count' => 1,
                'rejected_days' => 1,
                'total_days' => 6,
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
        $this->assertSame('Summary Cuti Sinta Audit Cuti', $worksheet->getCell('A1')->getValue());
        $this->assertSame('Pending: 1 requests / 2 hari', $worksheet->getCell('A2')->getValue());
        $this->assertSame('Approved: 1 requests / 3 hari', $worksheet->getCell('A3')->getValue());
        $this->assertSame('Rejected: 1 requests / 1 hari', $worksheet->getCell('A4')->getValue());
        $this->assertSame('Total Hari Cuti: 6', $worksheet->getCell('A5')->getValue());
        $this->assertSame('Jenis Cuti', $worksheet->getCell('A7')->getValue());
        $this->assertSame('Alasan', $worksheet->getCell('F7')->getValue());
        $this->assertSame('A7', $worksheet->getFreezePane());
        $this->assertSame(Alignment::VERTICAL_CENTER, $worksheet->getStyle('A2')->getAlignment()->getVertical());
        $this->assertSame(Alignment::VERTICAL_CENTER, $worksheet->getStyle('A5')->getAlignment()->getVertical());
        $this->assertTrue($worksheet->getColumnDimension('A')->getAutoSize());
        $this->assertTrue($worksheet->getColumnDimension('F')->getAutoSize());
        $this->assertSame('F9FAFB', $worksheet->getStyle('A8')->getFill()->getStartColor()->getRGB());
    }

    private function resolveLeaveTypeId(string $type): int
    {
        $definition = LeaveType::definitionFromInput($type);

        return (int) LeaveType::query()->firstOrCreate(
            ['tenant_id' => $this->tenant->id, 'name' => $definition['name']],
            ['is_paid' => $definition['is_paid']]
        )->id;
    }
}