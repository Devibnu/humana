<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeesCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_employee_form_is_full_wide_and_has_complete_fields(): void
    {
        [$admin, $tenant, $department, $position, $workLocation] = $this->createMasterData('employees-create-form');

        $response = $this->actingAs($admin)->get(route('employees.create'));

        $response->assertOk();
        $response->assertSee('Tambah Karyawan Baru');
        $response->assertSee('card mx-4 mb-4 shadow-xs', false);
        $response->assertSee('Informasi Personal');
        $response->assertSee('Informasi Pekerjaan');
        $response->assertSee('Lokasi Kerja');
        $response->assertSee('Koneksi Akun');
        $response->assertSee('Data keluarga dan rekening dapat dikelola setelah karyawan tersimpan.');
        $response->assertDontSee('Data Keluarga');
        $response->assertDontSee('Informasi Keuangan');
        $response->assertDontSee('+ Tambah Anggota');
        $response->assertDontSee('+ Tambah Rekening');

        // Personal fields
        $response->assertSee('name="name"', false);
        $response->assertSee('name="employee_code"', false);
        $response->assertSee('name="ktp_number"', false);
        $response->assertSee('name="kk_number"', false);
        $response->assertSee('name="education"', false);
        $response->assertSee('name="dob"', false);
        $response->assertSee('name="gender"', false);
        $response->assertSee('name="address"', false);
        $response->assertSee('name="email"', false);
        $response->assertSee('name="phone"', false);
        $response->assertSee('name="avatar"', false);

        // Employment fields
        $response->assertSee('name="department_id"', false);
        $response->assertSee('name="position_id"', false);
        $response->assertSee('name="role"', false);
        $response->assertSee('name="status"', false);
        $response->assertSee('name="start_date"', false);

        // Lokasi kerja + koneksi akun
        $response->assertSee('name="work_location_id"', false);
        $response->assertSee('name="user_id"', false);
        $response->assertDontSee('name="family_members[0][name]"', false);
        $response->assertDontSee('name="family_members[0][relationship]"', false);
        $response->assertDontSee('name="family_members[0][dob]"', false);
        $response->assertDontSee('name="family_members[0][education]"', false);
        $response->assertDontSee('name="family_members[0][job]"', false);
        $response->assertDontSee('name="family_members[0][marital_status]"', false);
        $response->assertDontSee('name="bank_accounts[0][bank_name]"', false);
        $response->assertDontSee('name="bank_accounts[0][account_number]"', false);
        $response->assertDontSee('name="bank_accounts[0][account_holder]"', false);
        $response->assertSee('Info Validasi Absensi');

        // Master data options rendered
        $response->assertSee($department->name);
        $response->assertSee($position->name);
        $response->assertSee($workLocation->name);

        // Education options
        $response->assertSee('S1');
        $response->assertSee('S2');
        $response->assertSee('SMA');
    }

    public function test_store_validates_employee_code_unique_within_tenant(): void
    {
        [$admin, $tenant, $department, $position, $workLocation] = $this->createMasterData('employees-create-dup-code');

        Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'EMP-001',
            'name' => 'Existing Employee',
            'email' => 'existing-employee@example.test',
            'role' => 'staff',
            'department_id' => $department->id,
            'position_id' => $position->id,
            'work_location_id' => $workLocation->id,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post(route('employees.store'), [
                'tenant_id' => $tenant->id,
                'employee_code' => 'EMP-001',
                'name' => 'Duplicate Code Employee',
                'email' => 'duplicate-code@example.test',
                'role' => 'staff',
                'department_id' => $department->id,
                'position_id' => $position->id,
                'work_location_id' => $workLocation->id,
                'status' => 'active',
            ])
            ->assertSessionHasErrors('employee_code');
    }

    public function test_store_validates_ktp_number_unique_within_tenant(): void
    {
        [$admin, $tenant, $department, $position, $workLocation] = $this->createMasterData('employees-ktp-dup');

        Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'EMP-KTP-001',
            'name' => 'First Employee',
            'email' => 'first-ktp@example.test',
            'ktp_number' => '3201234567890001',
            'role' => 'staff',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post(route('employees.store'), [
                'tenant_id' => $tenant->id,
                'employee_code' => 'EMP-KTP-002',
                'name' => 'Duplicate KTP Employee',
                'email' => 'second-ktp@example.test',
                'ktp_number' => '3201234567890001',
                'role' => 'staff',
                'status' => 'active',
            ])
            ->assertSessionHasErrors('ktp_number');
    }

    public function test_store_validates_email_format(): void
    {
        [$admin, $tenant, $department, $position, $workLocation] = $this->createMasterData('employees-create-email-format');

        $this->actingAs($admin)
            ->post(route('employees.store'), [
                'tenant_id' => $tenant->id,
                'employee_code' => 'EMP-EMAIL-1',
                'name' => 'Bad Email Employee',
                'email' => 'bad-email-format',
                'role' => 'staff',
                'department_id' => $department->id,
                'position_id' => $position->id,
                'work_location_id' => $workLocation->id,
                'status' => 'active',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_store_validates_gender_in_allowed_values(): void
    {
        [$admin, $tenant] = $this->createMasterData('employees-gender-invalid');

        $this->actingAs($admin)
            ->post(route('employees.store'), [
                'tenant_id' => $tenant->id,
                'employee_code' => 'EMP-GEN-1',
                'name' => 'Gender Test',
                'email' => 'gender-test@example.test',
                'gender' => 'unknown',
                'role' => 'staff',
                'status' => 'active',
            ])
            ->assertSessionHasErrors('gender');
    }

    public function test_store_validates_education_in_allowed_values(): void
    {
        [$admin, $tenant] = $this->createMasterData('employees-edu-invalid');

        $this->actingAs($admin)
            ->post(route('employees.store'), [
                'tenant_id' => $tenant->id,
                'employee_code' => 'EMP-EDU-1',
                'name' => 'Education Test',
                'email' => 'edu-test@example.test',
                'education' => 'SARJANA',
                'role' => 'staff',
                'status' => 'active',
            ])
            ->assertSessionHasErrors('education');
    }

    public function test_admin_can_store_employee_with_all_personal_and_employment_fields(): void
    {
        Storage::fake('public');

        [$admin, $tenant, $department, $position, $workLocation] = $this->createMasterData('employees-create-store');

        $avatar = UploadedFile::fake()->image('employee-avatar.png');

        $this->actingAs($admin)
            ->post(route('employees.store'), [
                'tenant_id'       => $tenant->id,
                'employee_code'   => 'EMP-STORE-001',
                'name'            => 'Stored Employee',
                'email'           => 'stored-employee@example.test',
                'phone'           => '08123456789',
                'ktp_number'      => '3271010101010001',
                'kk_number'       => '3271010101010002',
                'education'       => 'S1',
                'dob'             => '1990-06-15',
                'gender'          => 'male',
                'address'         => 'Jl. Sudirman No. 1, Jakarta Pusat',
                'avatar'          => $avatar,
                'department_id'   => $department->id,
                'position_id'     => $position->id,
                'role'            => 'supervisor',
                'work_location_id'=> $workLocation->id,
                'status'          => 'active',
                'start_date'      => '2026-04-19',
            ])
            ->assertRedirect(route('employees.index'))
            ->assertSessionHas('success', 'Employee berhasil dibuat.');

        $employee = Employee::where('employee_code', 'EMP-STORE-001')->firstOrFail();

        $this->assertSame($tenant->id, $employee->tenant_id);
        $this->assertSame($department->id, $employee->department_id);
        $this->assertSame($position->id, $employee->position_id);
        $this->assertSame($workLocation->id, $employee->work_location_id);
        $this->assertSame('supervisor', $employee->role);
        $this->assertSame('2026-04-19', optional($employee->start_date)->format('Y-m-d'));
        $this->assertSame('3271010101010001', $employee->ktp_number);
        $this->assertSame('3271010101010002', $employee->kk_number);
        $this->assertSame('S1', $employee->education);
        $this->assertSame('1990-06-15', optional($employee->dob)->format('Y-m-d'));
        $this->assertSame('male', $employee->gender);
        $this->assertSame('Jl. Sudirman No. 1, Jakarta Pusat', $employee->address);
        $this->assertNotNull($employee->avatar_path);
        Storage::disk('public')->assertExists($employee->avatar_path);
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
            'name' => 'Employee Create Admin',
            'email' => 'admin-'.$slug.'@example.test',
            'password' => 'password123',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $department = Department::create([
            'tenant_id' => $tenant->id,
            'name' => 'People Operations',
            'status' => 'active',
        ]);

        $position = Position::create([
            'tenant_id' => $tenant->id,
            'name' => 'HR Specialist',
            'status' => 'active',
        ]);

        $workLocation = WorkLocation::create([
            'tenant_id' => $tenant->id,
            'name' => 'Jakarta HQ',
            'address' => 'Jakarta',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'radius' => 100,
        ]);

        return [$admin, $tenant, $department, $position, $workLocation];
    }
}
