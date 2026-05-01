<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeCreateWithFinanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_employee_with_two_bank_accounts_in_one_submission(): void
    {
        [$admin, $tenant, $department, $position, $workLocation] = $this->createMasterData('employee-create-with-finance');

        $response = $this->actingAs($admin)
            ->post(route('employees.store'), [
                'tenant_id' => $tenant->id,
                'employee_code' => 'EMP-BANK-STORE-001',
                'name' => 'Karyawan Dengan Rekening',
                'email' => 'karyawan-dengan-rekening@example.test',
                'phone' => '081234567891',
                'role' => 'staff',
                'department_id' => $department->id,
                'position_id' => $position->id,
                'work_location_id' => $workLocation->id,
                'status' => 'active',
                'bank_accounts' => [
                    [
                        'bank_name' => 'BCA',
                        'account_number' => '1234567890',
                        'account_holder' => 'Karyawan Dengan Rekening',
                    ],
                    [
                        'bank_name' => 'Mandiri',
                        'account_number' => '0987654321',
                        'account_holder' => 'Karyawan Dengan Rekening',
                    ],
                ],
            ]);

        $response->assertRedirect(route('employees.index'));
        $response->assertSessionHas('success', 'Employee berhasil dibuat.');

        $employee = Employee::where('employee_code', 'EMP-BANK-STORE-001')->firstOrFail();

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'tenant_id' => $tenant->id,
            'name' => 'Karyawan Dengan Rekening',
        ]);

        $this->assertDatabaseHas('bank_accounts', [
            'employee_id' => $employee->id,
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder' => 'Karyawan Dengan Rekening',
        ]);

        $this->assertDatabaseHas('bank_accounts', [
            'employee_id' => $employee->id,
            'bank_name' => 'Mandiri',
            'account_number' => '0987654321',
            'account_holder' => 'Karyawan Dengan Rekening',
        ]);

        $this->assertSame(2, $employee->bankAccounts()->count());
    }

    protected function createMasterData(string $slug): array
    {
        $tenant = Tenant::create([
            'name' => ucfirst(str_replace('-', ' ', $slug)).' Tenant',
            'slug' => $slug,
            'domain' => $slug.'.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Employee Create Finance Admin',
            'email' => 'admin-'.$slug.'@example.test',
            'password' => 'password123',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $department = Department::create([
            'tenant_id' => $tenant->id,
            'name' => 'Finance Operations',
            'status' => 'active',
        ]);

        $position = Position::create([
            'tenant_id' => $tenant->id,
            'name' => 'Finance Staff',
            'status' => 'active',
        ]);

        $workLocation = WorkLocation::create([
            'tenant_id' => $tenant->id,
            'name' => 'Surabaya Office',
            'address' => 'Surabaya',
            'latitude' => -7.257472,
            'longitude' => 112.75209,
            'radius' => 150,
        ]);

        return [$admin, $tenant, $department, $position, $workLocation];
    }
}