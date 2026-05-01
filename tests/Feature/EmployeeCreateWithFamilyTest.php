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

class EmployeeCreateWithFamilyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_employee_with_two_family_members_in_one_submission(): void
    {
        [$admin, $tenant, $department, $position, $workLocation] = $this->createMasterData('employee-create-with-family');

        $response = $this->actingAs($admin)
            ->post(route('employees.store'), [
                'tenant_id' => $tenant->id,
                'employee_code' => 'EMP-FAM-STORE-001',
                'name' => 'Karyawan Dengan Keluarga',
                'email' => 'karyawan-dengan-keluarga@example.test',
                'phone' => '081234567890',
                'role' => 'staff',
                'department_id' => $department->id,
                'position_id' => $position->id,
                'work_location_id' => $workLocation->id,
                'status' => 'active',
                'family_members' => [
                    [
                        'name' => 'Siti Rahma',
                        'relationship' => 'pasangan',
                        'dob' => '1992-04-10',
                        'education' => 'S1',
                        'job' => 'Guru',
                        'marital_status' => 'menikah',
                    ],
                    [
                        'name' => 'Alya Putri',
                        'relationship' => 'anak',
                        'dob' => '2018-08-21',
                        'education' => 'TK',
                        'job' => 'Pelajar',
                        'marital_status' => 'belum_menikah',
                    ],
                ],
            ]);

        $response->assertRedirect(route('employees.index'));
        $response->assertSessionHas('success', 'Employee berhasil dibuat.');

        $employee = Employee::where('employee_code', 'EMP-FAM-STORE-001')->firstOrFail();

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'tenant_id' => $tenant->id,
            'name' => 'Karyawan Dengan Keluarga',
        ]);

        $this->assertDatabaseHas('family_members', [
            'employee_id' => $employee->id,
            'name' => 'Siti Rahma',
            'relationship' => 'pasangan',
            'dob' => '1992-04-10',
            'education' => 'S1',
            'job' => 'Guru',
            'marital_status' => 'menikah',
        ]);

        $this->assertDatabaseHas('family_members', [
            'employee_id' => $employee->id,
            'name' => 'Alya Putri',
            'relationship' => 'anak',
            'dob' => '2018-08-21',
            'education' => 'TK',
            'job' => 'Pelajar',
            'marital_status' => 'belum_menikah',
        ]);

        $this->assertSame(2, $employee->familyMembers()->count());
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
            'name' => 'Employee Create Family Admin',
            'email' => 'admin-'.$slug.'@example.test',
            'password' => 'password123',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $department = Department::create([
            'tenant_id' => $tenant->id,
            'name' => 'Human Capital',
            'status' => 'active',
        ]);

        $position = Position::create([
            'tenant_id' => $tenant->id,
            'name' => 'HR Officer',
            'status' => 'active',
        ]);

        $workLocation = WorkLocation::create([
            'tenant_id' => $tenant->id,
            'name' => 'Bandung Office',
            'address' => 'Bandung',
            'latitude' => -6.914744,
            'longitude' => 107.60981,
            'radius' => 150,
        ]);

        return [$admin, $tenant, $department, $position, $workLocation];
    }
}