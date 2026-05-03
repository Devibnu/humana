<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobilePayslipApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
    }

    public function test_employee_can_list_only_own_mobile_payslips(): void
    {
        $tenant = $this->makeTenant();
        [$user, $employee] = $this->makeEmployeeUser($tenant, 'self');
        [, $otherEmployee] = $this->makeEmployeeUser($tenant, 'other');

        $ownPayroll = $this->makePayroll($employee);
        $this->makePayroll($otherEmployee);

        Sanctum::actingAs($user);

        $this->getJson('/api/mobile/payslips')
            ->assertOk()
            ->assertJsonPath('summary.total', 1)
            ->assertJsonPath('data.0.id', $ownPayroll->id)
            ->assertJsonPath('data.0.net_salary', 5575000);
    }

    public function test_employee_cannot_open_other_employee_mobile_payslip(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->makeEmployeeUser($tenant, 'self');
        [, $otherEmployee] = $this->makeEmployeeUser($tenant, 'other');
        $otherPayroll = $this->makePayroll($otherEmployee);

        Sanctum::actingAs($user);

        $this->getJson("/api/mobile/payslips/{$otherPayroll->id}")
            ->assertForbidden();
    }

    public function test_mobile_payslip_requires_payslip_permission(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->makeEmployeeUser($tenant, 'no-slip');

        RolePermission::query()
            ->where('role_id', $user->role_id)
            ->where('menu_key', 'payroll.slips')
            ->delete();

        Sanctum::actingAs($user);

        $this->getJson('/api/mobile/payslips')->assertForbidden();
    }

    protected function makeTenant(): Tenant
    {
        return Tenant::create([
            'name' => 'Mobile Payslip Tenant',
            'slug' => 'mobile-payslip-tenant',
            'domain' => 'mobile-payslip-tenant.test',
            'status' => 'active',
        ]);
    }

    protected function makeEmployeeUser(Tenant $tenant, string $suffix): array
    {
        $role = Role::where('name', 'Employee')->firstOrFail();
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Mobile Payslip Employee '.$suffix,
            'email' => "mobile-payslip-{$suffix}@example.test",
            'password' => 'password123',
            'role_id' => $role->id,
            'role' => 'employee',
            'status' => 'active',
        ]);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'employee_code' => 'MPAY-'.$suffix,
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
            'deduction_attendance' => 75000,
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
        ]);
    }
}
