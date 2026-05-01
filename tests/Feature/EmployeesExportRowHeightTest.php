<?php

namespace Tests\Feature;

use App\Exports\EmployeesExport;
use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class EmployeesExportRowHeightTest extends TestCase
{
    use RefreshDatabase;

    public function test_employees_export_applies_minimum_header_and_body_row_heights(): void
    {
        $tenant = Tenant::create([
            'name' => 'Employees Row Height Tenant',
            'slug' => 'employees-row-height-tenant',
            'domain' => 'employees-row-height-tenant.test',
            'status' => 'active',
        ]);

        $linkedUser = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Employees Row Height User',
            'email' => 'employees-row-height-user@example.test',
            'password' => 'password',
            'role' => 'employee',
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $linkedUser->id,
            'employee_code' => 'EMP-RHT-1',
            'name' => 'Employees Row Height Export',
            'email' => 'employees-row-height-export@example.test',
            'status' => 'active',
        ]);

        $export = new EmployeesExport(Employee::with(['tenant', 'user', 'position', 'department'])->get(), [
            'tenant' => $tenant->id,
            'tenant_name' => $tenant->name,
            'linked' => 'only',
        ]);

        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        foreach ($export->headings() as $index => $heading) {
            $worksheet->setCellValueByColumnAndRow($index + 1, 1, $heading);
        }

        $worksheet->setCellValue('A2', 'EMP-RHT-1');
        $worksheet->setCellValue('B2', 'Body Row');

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

        $this->assertSame(25, $worksheet->getRowDimension(1)->getRowHeight());
        $this->assertSame(20, $worksheet->getRowDimension(2)->getRowHeight());
    }
}