<?php

namespace Tests\Feature;

use App\Exports\UsersExport;
use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class UsersExportXlsxComfortTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_export_freezes_header_row_and_autosizes_columns(): void
    {
        $tenant = Tenant::create([
            'name' => 'Users Comfort Tenant',
            'slug' => 'users-comfort-tenant',
            'domain' => 'users-comfort-tenant.test',
            'status' => 'active',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Comfort Export User',
            'email' => 'comfort-export-user@example.test',
            'password' => 'password',
            'role' => 'employee',
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'employee_code' => 'USR-COM-1',
            'name' => 'Comfort Export Employee',
            'email' => 'comfort-export-employee@example.test',
            'status' => 'active',
        ]);

        $export = new UsersExport(User::with(['tenant', 'employee'])->get(), [
            'tenant' => $tenant->id,
            'role' => 'employee',
            'linked' => 'only',
        ]);

        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        foreach ($export->headings() as $index => $heading) {
            $worksheet->setCellValueByColumnAndRow($index + 1, 1, $heading);
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

        $this->assertSame('A2', $worksheet->getFreezePane());
        $this->assertTrue($worksheet->getColumnDimension('A')->getAutoSize());
        $this->assertTrue($worksheet->getColumnDimension('H')->getAutoSize());
    }
}