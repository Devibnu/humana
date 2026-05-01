<?php

namespace Tests\Feature;

use App\Exports\UsersExport;
use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class UsersExportXlsxStyleTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_export_applies_wrap_text_and_vertical_alignment(): void
    {
        $tenant = Tenant::create([
            'name' => 'Users Wrap Tenant',
            'slug' => 'users-wrap-tenant',
            'domain' => 'users-wrap-tenant.test',
            'status' => 'active',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Wrap Export User',
            'email' => 'wrap-export-user@example.test',
            'password' => 'password',
            'role' => 'employee',
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'employee_code' => 'USR-WRP-1',
            'name' => 'Wrap Export Employee',
            'email' => 'wrap-export-employee@example.test',
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
        $worksheet->setCellValue('B2', 'Long User Name');

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