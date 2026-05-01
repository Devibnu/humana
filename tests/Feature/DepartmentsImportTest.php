<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class DepartmentsImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_hr_can_import_departments_from_excel_and_update_existing_rows(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Import Departemen',
            'code' => 'TEN-IMP-DEP',
            'slug' => 'tenant-import-departemen',
            'domain' => 'tenant-import-departemen.test',
            'status' => 'active',
        ]);

        Department::create([
            'tenant_id' => $tenant->id,
            'name' => 'Finance & Accounting',
            'code' => 'FIN-OLD',
            'description' => 'Deskripsi lama.',
            'status' => 'inactive',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Import Departemen',
            'email' => 'admin-import-departemen@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $file = $this->makeExcelUpload([
            ['tenant_code', 'name', 'code', 'description', 'status'],
            ['TEN-IMP-DEP', 'Finance & Accounting', 'FIN', 'Laporan keuangan dan budgeting.', 'active'],
            ['TEN-IMP-DEP', 'Human Capital', 'HC', 'Pengelolaan SDM dan budaya kerja.', 'aktif'],
        ]);

        $response = $this->actingAs($admin)->post(route('departments.import'), [
            'department_import_file' => $file,
        ]);

        $response->assertRedirect(route('departments.index'));
        $response->assertSessionHas('success', 'Import departemen berhasil: 1 ditambahkan, 1 diperbarui.');

        $this->assertDatabaseHas('departments', [
            'tenant_id' => $tenant->id,
            'name' => 'Finance & Accounting',
            'code' => 'FIN',
            'description' => 'Laporan keuangan dan budgeting.',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('departments', [
            'tenant_id' => $tenant->id,
            'name' => 'Human Capital',
            'code' => 'HC',
            'description' => 'Pengelolaan SDM dan budaya kerja.',
            'status' => 'active',
        ]);
    }

    public function test_import_departments_rejects_invalid_tenant_data(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Invalid Import',
            'code' => 'TEN-INV-DEP',
            'slug' => 'tenant-invalid-import',
            'domain' => 'tenant-invalid-import.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Invalid Import',
            'email' => 'admin-invalid-import@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $file = $this->makeExcelUpload([
            ['tenant_code', 'name', 'code', 'description', 'status'],
            ['TEN-TIDAK-ADA', 'Operasional', 'OPS', 'Tim operasional.', 'active'],
        ]);

        $response = $this->actingAs($admin)->post(route('departments.import'), [
            'department_import_file' => $file,
        ]);

        $response->assertRedirect(route('departments.index'));
        $response->assertSessionHas('error', 'Import departemen gagal. Periksa detail error pada modal import.');
        $response->assertSessionHas('open_department_import_modal', true);
        $this->assertSame(0, Department::count());
    }

    public function test_employee_cannot_import_departments(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Import Forbidden',
            'code' => 'TEN-FBD-DEP',
            'slug' => 'tenant-import-forbidden',
            'domain' => 'tenant-import-forbidden.test',
            'status' => 'active',
        ]);

        $employee = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Employee Import Forbidden',
            'email' => 'employee-import-forbidden@example.test',
            'password' => 'password',
            'role' => 'employee',
            'status' => 'active',
        ]);

        $file = $this->makeExcelUpload([
            ['tenant_code', 'name'],
            ['TEN-FBD-DEP', 'Legal'],
        ]);

        $this->actingAs($employee)
            ->post(route('departments.import'), [
                'department_import_file' => $file,
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

        $path = tempnam(sys_get_temp_dir(), 'departments-import');
        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return new UploadedFile(
            $path,
            'departments-import.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
    }
}
