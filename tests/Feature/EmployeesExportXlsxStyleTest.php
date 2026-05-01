<?php

namespace Tests\Feature;

use App\Exports\EmployeesExport;
use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class EmployeesExportXlsxStyleTest extends TestCase
{
    use RefreshDatabase;

    public function test_employees_export_applies_wrap_text_and_vertical_alignment(): void
    {
        $tenant = Tenant::create([
            'name' => 'Employees Wrap Tenant',
            'slug' => 'employees-wrap-tenant',
            'domain' => 'employees-wrap-tenant.test',
            'status' => 'active',
        ]);

        $linkedUser = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Wrap Employee User',
            'email' => 'wrap-employee-user@example.test',
            'password' => 'password',
            'role' => 'employee',
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $linkedUser->id,
            'employee_code' => 'EMP-WRP-1',
            'name' => 'Wrap Employee Export',
            'email' => 'wrap-employee-export@example.test',
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

        $worksheet->setCellValue('A2', 'EMP-WRP-1');
        $worksheet->setCellValue('B2', 'Long Employee Name');

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

        $alignment = $worksheet->getStyle('A1')->getAlignment();

        $this->assertTrue($alignment->getWrapText());
        $this->assertSame(Alignment::VERTICAL_CENTER, $alignment->getVertical());
    }
}