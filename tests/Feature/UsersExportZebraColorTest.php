<?php

namespace Tests\Feature;

use App\Exports\UsersExport;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class UsersExportZebraColorTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_export_applies_light_blue_zebra_striping_to_even_rows(): void
    {
        $tenant = Tenant::create([
            'name' => 'Users Zebra Tenant',
            'slug' => 'users-zebra-tenant',
            'domain' => 'users-zebra-tenant.test',
            'status' => 'active',
        ]);

        User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Users Zebra Export',
            'email' => 'users-zebra-export@example.test',
            'password' => 'password',
            'role' => 'employee',
            'status' => 'active',
        ]);

        $export = new UsersExport(User::with(['tenant', 'employee'])->get(), [
            'tenant' => $tenant->id,
            'tenant_name' => $tenant->name,
            'role' => 'employee',
            'linked' => 'unlinked',
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
        $this->assertNotSame('EFF6FF', $worksheet->getStyle('A3')->getFill()->getStartColor()->getRGB());
    }
}