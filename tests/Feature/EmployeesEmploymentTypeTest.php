<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeesEmploymentTypeTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name'   => 'Employment Type Tenant',
            'slug'   => 'employment-type-test',
            'domain' => 'employment-type.test',
            'status' => 'active',
        ]);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Employment Admin',
            'email'     => 'employment-admin@example.test',
            'password'  => 'password',
            'role'      => 'admin_hr',
            'status'    => 'active',
        ]);
    }

    private function baseEmployeeData(array $overrides = []): array
    {
        return array_merge([
            'tenant_id'     => $this->tenant->id,
            'employee_code' => 'EMP-EMP-001',
            'name'          => 'Employment Test Employee',
            'email'         => 'employment-emp@example.test',
            'status'        => 'active',
            'role'          => 'staff',
        ], $overrides);
    }

    public function test_can_create_employee_with_employment_type_tetap(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('employees.store'), $this->baseEmployeeData([
                'employment_type' => 'tetap',
            ]));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('employees', [
            'employee_code'   => 'EMP-EMP-001',
            'employment_type' => 'tetap',
        ]);
    }

    public function test_can_create_employee_with_employment_type_kontrak_and_dates(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('employees.store'), $this->baseEmployeeData([
                'employment_type'     => 'kontrak',
                'contract_start_date' => '2026-05-01',
                'contract_end_date'   => '2027-04-30',
            ]));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('employees', [
            'employee_code'   => 'EMP-EMP-001',
            'employment_type' => 'kontrak',
        ]);

        $employee = Employee::where('employee_code', 'EMP-EMP-001')->first();
        $this->assertNotNull($employee->contract_start_date);
        $this->assertNotNull($employee->contract_end_date);
        $this->assertEquals('2026-05-01', $employee->contract_start_date->format('Y-m-d'));
        $this->assertEquals('2027-04-30', $employee->contract_end_date->format('Y-m-d'));
    }

    public function test_kontrak_requires_contract_start_date(): void
    {
        $this->actingAs($this->admin)
            ->post(route('employees.store'), $this->baseEmployeeData([
                'employment_type'   => 'kontrak',
                'contract_end_date' => '2027-04-30',
            ]))
            ->assertSessionHasErrors('contract_start_date');
    }

    public function test_kontrak_requires_contract_end_date(): void
    {
        $this->actingAs($this->admin)
            ->post(route('employees.store'), $this->baseEmployeeData([
                'employment_type'     => 'kontrak',
                'contract_start_date' => '2026-05-01',
            ]))
            ->assertSessionHasErrors('contract_end_date');
    }

    public function test_contract_end_date_must_be_after_or_equal_start_date(): void
    {
        $this->actingAs($this->admin)
            ->post(route('employees.store'), $this->baseEmployeeData([
                'employment_type'     => 'kontrak',
                'contract_start_date' => '2026-05-01',
                'contract_end_date'   => '2026-04-01',
            ]))
            ->assertSessionHasErrors('contract_end_date');
    }

    public function test_tetap_does_not_require_contract_dates(): void
    {
        $this->actingAs($this->admin)
            ->post(route('employees.store'), $this->baseEmployeeData([
                'employment_type' => 'tetap',
            ]))
            ->assertSessionHasNoErrors();
    }

    public function test_employment_type_rejects_invalid_value(): void
    {
        $this->actingAs($this->admin)
            ->post(route('employees.store'), $this->baseEmployeeData([
                'employment_type' => 'freelance',
            ]))
            ->assertSessionHasErrors('employment_type');
    }

    public function test_employee_show_displays_employment_type(): void
    {
        $employee = Employee::create([
            'tenant_id'       => $this->tenant->id,
            'employee_code'   => 'EMP-SHOW-001',
            'name'            => 'Show Employment Employee',
            'email'           => 'show-emp@example.test',
            'status'          => 'active',
            'employment_type' => 'kontrak',
            'contract_start_date' => '2026-01-01',
            'contract_end_date'   => '2026-12-31',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('employees.show', $employee));

        $response->assertOk();
        $response->assertSee('Kontrak');
        $response->assertSee('Status Pekerjaan');
    }

    public function test_employee_show_displays_marital_status(): void
    {
        $employee = Employee::create([
            'tenant_id'      => $this->tenant->id,
            'employee_code'  => 'EMP-MARITAL-001',
            'name'           => 'Marital Status Employee',
            'email'          => 'marital-emp@example.test',
            'status'         => 'active',
            'marital_status' => 'menikah',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('employees.show', $employee));

        $response->assertOk();
        $response->assertSee('Menikah');
        $response->assertSee('Status Pernikahan');
    }
}
