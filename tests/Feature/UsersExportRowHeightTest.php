<?php

namespace Tests\Feature;

use App\Exports\UsersExport;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class UsersExportRowHeightTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_export_applies_minimum_header_and_body_row_heights(): void
    {
        $tenant = Tenant::create([
            'name' => 'Users Row Height Tenant',
            'slug' => 'users-row-height-tenant',
            'domain' => 'users-row-height-tenant.test',
            'status' => 'active',
        ]);

        User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Users Row Height Export',
            'email' => 'users-row-height-export@example.test',
            'password' => 'password',
            'role' => 'employee',
            'status' => 'active',
        ]);

        $export = new UsersExport(User::with(['tenant', 'employee'])->get(), [
            'tenant' => $tenant->id,
            'tenant_name' => $tenant->name,
            'role' => 'employee',
        ]);

        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        foreach ($export->headings() as $index => $heading) {
            $worksheet->setCellValueByColumnAndRow($index + 1, 1, $heading);
        }

        $worksheet->setCellValue('A2', '1');
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