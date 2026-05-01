<?php

namespace Tests\Feature;

use App\Exports\UsersExport;
use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class UsersExportXlsxStyleComfortTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_export_applies_zebra_striping_and_thin_borders_to_body(): void
    {
        $tenant = Tenant::create([
            'name' => 'Users Comfort Styled Tenant',
            'slug' => 'users-comfort-styled-tenant',
            'domain' => 'users-comfort-styled-tenant.test',
            'status' => 'active',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Comfort Styled User',
            'email' => 'comfort-styled-user@example.test',
            'password' => 'password',
            'role' => 'employee',
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'employee_code' => 'USR-ZBR-1',
            'name' => 'Comfort Styled Employee',
            'email' => 'comfort-styled-employee@example.test',
            'status' => 'active',
        ]);

        $export = new UsersExport(User::with(['tenant', 'employee'])->get(), [
            'tenant' => $tenant->id,
            'tenant_name' => $tenant->name,
            'role' => 'employee',
            'linked' => 'only',
        ]);

        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        foreach ($export->headings() as $index => $heading) {
            $worksheet->setCellValueByColumnAndRow($index + 1, 1, $heading);
        }

        $worksheet->setCellValue('A2', '1');
        $worksheet->setCellValue('B2', 'Even Row');
        $worksheet->setCellValue('A3', '2');
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

        $this->assertSame('EFF6FF', $worksheet->getStyle('A2')->getFill()->getStartColor()->getRGB());
        $this->assertSame(Border::BORDER_THIN, $worksheet->getStyle('A2')->getBorders()->getLeft()->getBorderStyle());
    }
}