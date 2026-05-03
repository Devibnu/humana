<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyPayslipAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
    }

    public function test_employee_can_only_open_own_payslips(): void
    {
        $tenant = $this->makeTenant();
        [$user, $employee] = $this->makeEmployeeUser($tenant, 'self');
        [, $otherEmployee] = $this->makeEmployeeUser($tenant, 'other');

        $ownPayroll = $this->makePayroll($employee);
        $otherPayroll = $this->makePayroll($otherEmployee);

        $this->actingAs($user)
            ->get(route('my-payslips.index'))
            ->assertOk()
            ->assertSee('Slip Gaji Saya')
            ->assertSee('01 Jan 2026 - 31 Jan 2026')
            ->assertDontSee($otherEmployee->name);

        $this->actingAs($user)
            ->get(route('my-payslips.show', $ownPayroll))
            ->assertOk()
            ->assertSee('Detail Slip Gaji')
            ->assertDontSee(route('payroll.edit', $ownPayroll), false);

        $this->actingAs($user)
            ->get(route('my-payslips.show', $otherPayroll))
            ->assertForbidden();
    }

    public function test_employee_still_cannot_open_payroll_admin_module(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->makeEmployeeUser($tenant, 'admin-block');

        $this->actingAs($user)->get(route('payroll.index'))->assertForbidden();
    }

    protected function makeTenant(): Tenant
    {
        return Tenant::create([
            'name' => 'Payslip Tenant',
            'slug' => 'payslip-tenant',
            'domain' => 'payslip-tenant.test',
            'status' => 'active',
        ]);
    }

    protected function makeEmployeeUser(Tenant $tenant, string $suffix): array
    {
        $role = Role::where('name', 'Employee')->firstOrFail();
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Payslip Employee '.$suffix,
            'email' => "payslip-{$suffix}@example.test",
            'password' => 'password123',
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'employee_code' => 'PAY-'.$suffix,
            'name' => $user->name,
            'email' => $user->email,
            'status' => 'active',
        ]);

        return [$user, $employee];
    }

    protected function makePayroll(Employee $employee): Payroll
    {
        return Payroll::create([
            'employee_id' => $employee->id,
            'monthly_salary' => 5000000,
            'allowance_transport' => 250000,
            'allowance_meal' => 300000,
            'allowance_health' => 150000,
            'overtime_pay' => 100000,
            'deduction_tax' => 50000,
            'deduction_bpjs' => 100000,
            'deduction_loan' => 0,
            'deduction_attendance' => 25000,
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
        ]);
    }
}
