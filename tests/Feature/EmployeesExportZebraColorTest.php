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

class EmployeesExportZebraColorTest extends TestCase
{
    use RefreshDatabase;

    public function test_employees_export_applies_light_green_zebra_striping_to_even_rows(): void
    {
        $tenant = Tenant::create([
            'name' => 'Employees Zebra Tenant',
            'slug' => 'employees-zebra-tenant',
            'domain' => 'employees-zebra-tenant.test',
            'status' => 'active',
        ]);

        $linkedUser = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Employees Zebra User',
            'email' => 'employees-zebra-user@example.test',
            'password' => 'password',
            'role' => 'employee',
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $linkedUser->id,
            'employee_code' => 'EMP-ZEB-1',
            'name' => 'Employees Zebra Export',
            'email' => 'employees-zebra-export@example.test',
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

        $worksheet->setCellValue('A2', 'EMP-ZEB-1');
        $worksheet->setCellValue('B2', 'Even Row');
        $worksheet->setCellValue('A3', 'EMP-ZEB-2');
        $worksheet->setCellValue('B3', 'Odd Row');

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

        $this->assertSame('F0FDF4', $worksheet->getStyle('A2')->getFill()->getStartColor()->getRGB());
        $this->assertNotSame('F0FDF4', $worksheet->getStyle('A3')->getFill()->getStartColor()->getRGB());
    }
}