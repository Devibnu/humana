<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Position;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class PositionsImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_hr_can_import_positions_from_excel_and_update_existing_rows(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Import Posisi',
            'code' => 'TEN-IMP-POS',
            'slug' => 'tenant-import-posisi',
            'domain' => 'tenant-import-posisi.test',
            'status' => 'active',
        ]);

        $finance = Department::create([
            'tenant_id' => $tenant->id,
            'name' => 'Finance',
            'code' => 'FIN',
            'status' => 'active',
        ]);

        $humanCapital = Department::create([
            'tenant_id' => $tenant->id,
            'name' => 'Human Capital',
            'code' => 'HC',
            'status' => 'active',
        ]);

        Position::create([
            'tenant_id' => $tenant->id,
            'department_id' => $finance->id,
            'name' => 'Finance Manager',
            'code' => 'FIN-OLD',
            'description' => 'Deskripsi lama.',
            'status' => 'inactive',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Import Posisi',
            'email' => 'admin-import-posisi@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $file = $this->makeExcelUpload([
            ['tenant_code', 'department_code', 'name', 'code', 'description', 'status'],
            ['TEN-IMP-POS', 'FIN', 'Finance Manager', 'FIN-MGR', 'Memimpin tim keuangan.', 'active'],
            ['TEN-IMP-POS', 'HC', 'HR Officer', 'HC-OFC', 'Menjalankan operasional SDM.', 'aktif'],
        ]);

        $response = $this->actingAs($admin)->post(route('positions.import'), [
            'position_import_file' => $file,
        ]);

        $response->assertRedirect(route('positions.index'));
        $response->assertSessionHas('success', 'Import posisi berhasil: 1 ditambahkan, 1 diperbarui.');

        $this->assertDatabaseHas('positions', [
            'tenant_id' => $tenant->id,
            'department_id' => $finance->id,
            'name' => 'Finance Manager',
            'code' => 'FIN-MGR',
            'description' => 'Memimpin tim keuangan.',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('positions', [
            'tenant_id' => $tenant->id,
            'department_id' => $humanCapital->id,
            'name' => 'HR Officer',
            'code' => 'HC-OFC',
            'description' => 'Menjalankan operasional SDM.',
            'status' => 'active',
        ]);
    }

    public function test_import_positions_rejects_invalid_department_data(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Invalid Import Posisi',
            'code' => 'TEN-INV-POS',
            'slug' => 'tenant-invalid-import-posisi',
            'domain' => 'tenant-invalid-import-posisi.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Invalid Import Posisi',
            'email' => 'admin-invalid-import-posisi@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $file = $this->makeExcelUpload([
            ['tenant_code', 'department_code', 'name', 'code', 'description', 'status'],
            ['TEN-INV-POS', 'XXX', 'Operasional Lead', 'OPS-01', 'Tim operasional.', 'active'],
        ]);

        $response = $this->actingAs($admin)->post(route('positions.import'), [
            'position_import_file' => $file,
        ]);

        $response->assertRedirect(route('positions.index'));
        $response->assertSessionHas('error', 'Import posisi gagal. Periksa detail error pada modal import.');
        $response->assertSessionHas('open_position_import_modal', true);
        $this->assertSame(0, Position::count());
    }

    public function test_employee_cannot_import_positions(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Import Posisi Forbidden',
            'code' => 'TEN-FBD-POS',
            'slug' => 'tenant-import-posisi-forbidden',
            'domain' => 'tenant-import-posisi-forbidden.test',
            'status' => 'active',
        ]);

        $employee = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Employee Import Posisi Forbidden',
            'email' => 'employee-import-posisi-forbidden@example.test',
            'password' => 'password',
            'role' => 'employee',
            'status' => 'active',
        ]);

        $file = $this->makeExcelUpload([
            ['tenant_code', 'department_code', 'name'],
            ['TEN-FBD-POS', 'OPS', 'Legal Officer'],
        ]);

        $this->actingAs($employee)
            ->post(route('positions.import'), [
                'position_import_file' => $file,
            ])
            ->assertForbidden();
    }

    protected function makeExcelUpload(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($rows as $rowIndex => $row) {
            foreach (array_values($row) as $columnIndex => $value) {
                $sheet->setCellValueByColumnAndRow($columnIndex + 1, $rowIndex + 1, $value);
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'positions-import');
        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return new UploadedFile(
            $path,
            'positions-import.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
    }
}