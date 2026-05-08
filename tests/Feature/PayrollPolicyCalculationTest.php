<?php

namespace Tests\Feature;

use App\Models\DeductionRule;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Payroll;
use App\Models\PayrollPolicy;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollPolicyCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_policy_can_deduct_paid_leave_meal_allowance_for_permanent_employee(): void
    {
        $this->seed(RolesTableSeeder::class);

        [$tenant, $admin, $rule] = $this->makePayrollContext('permanent');
        $employee = $this->makeEmployee($tenant, 'POL-PERM-001', 'tetap');
        $leaveType = LeaveType::create([
            'tenant_id' => $tenant->id,
            'name' => 'Izin Dibayar',
            'is_paid' => true,
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-05-04',
            'end_date' => '2026-05-04',
            'reason' => 'Izin keluarga',
            'status' => 'approved',
        ]);

        $policy = PayrollPolicy::create([
            'tenant_id' => $tenant->id,
            'name' => 'Tetap - potong makan saat paid leave',
            'employment_type' => 'tetap',
            'priority' => 10,
        ]);
        $policy->components()->create([
            'component_code' => 'meal_allowance',
            'component_name' => 'Uang Makan',
            'component_type' => 'earning',
            'amount' => 25000,
            'calculation_method' => 'daily_attendance',
            'leave_paid_behavior' => 'deduct_daily',
            'leave_unpaid_behavior' => 'deduct_daily',
        ]);

        $this->actingAs($admin)->post(route('payroll.store'), [
            'employee_id' => $employee->id,
            'deduction_rule_id' => $rule->id,
            'monthly_salary' => 7000000,
            'period_start' => '2026-05-01',
            'period_end' => '2026-05-31',
        ])->assertRedirect(route('payroll.index'));

        $payroll = Payroll::where('employee_id', $employee->id)->firstOrFail();

        $this->assertDatabaseHas('payroll_items', [
            'payroll_id' => $payroll->id,
            'component_code' => 'meal_allowance',
            'component_type' => 'deduction',
            'amount' => '25000.00',
        ]);
        $this->assertSame('25000.00', $payroll->refresh()->deduction_attendance);
    }

    public function test_policy_can_keep_paid_leave_meal_allowance_for_contract_employee(): void
    {
        $this->seed(RolesTableSeeder::class);

        [$tenant, $admin, $rule] = $this->makePayrollContext('contract');
        $employee = $this->makeEmployee($tenant, 'POL-CONT-001', 'kontrak');
        $leaveType = LeaveType::create([
            'tenant_id' => $tenant->id,
            'name' => 'Izin Dibayar',
            'is_paid' => true,
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-05-04',
            'end_date' => '2026-05-04',
            'reason' => 'Izin keluarga',
            'status' => 'approved',
        ]);

        $policy = PayrollPolicy::create([
            'tenant_id' => $tenant->id,
            'name' => 'Kontrak - paid leave tidak potong makan',
            'employment_type' => 'kontrak',
            'priority' => 10,
        ]);
        $policy->components()->create([
            'component_code' => 'meal_allowance',
            'component_name' => 'Uang Makan',
            'component_type' => 'earning',
            'amount' => 25000,
            'calculation_method' => 'daily_attendance',
            'leave_paid_behavior' => 'keep',
            'leave_unpaid_behavior' => 'deduct_daily',
        ]);

        $this->actingAs($admin)->post(route('payroll.store'), [
            'employee_id' => $employee->id,
            'deduction_rule_id' => $rule->id,
            'monthly_salary' => 7000000,
            'period_start' => '2026-05-01',
            'period_end' => '2026-05-31',
        ])->assertRedirect(route('payroll.index'));

        $payroll = Payroll::where('employee_id', $employee->id)->firstOrFail();

        $this->assertDatabaseMissing('payroll_items', [
            'payroll_id' => $payroll->id,
            'component_code' => 'meal_allowance',
            'component_type' => 'deduction',
        ]);
        $this->assertSame('0.00', $payroll->refresh()->deduction_attendance);
    }

    public function test_policy_can_apply_thr_and_other_allowance_components(): void
    {
        $this->seed(RolesTableSeeder::class);

        [$tenant, $admin, $rule] = $this->makePayrollContext('thr-other');
        $employee = $this->makeEmployee($tenant, 'POL-THR-001', 'tetap');

        $policy = PayrollPolicy::create([
            'tenant_id' => $tenant->id,
            'name' => 'Tetap - THR dan tunjangan lainnya',
            'employment_type' => 'tetap',
            'priority' => 10,
        ]);
        $policy->components()->create([
            'component_code' => 'thr_allowance',
            'component_name' => 'THR',
            'component_type' => 'earning',
            'amount' => 2500000,
            'calculation_method' => 'flat',
            'sort_order' => 10,
        ]);
        $policy->components()->create([
            'component_code' => 'other_allowance',
            'component_name' => 'Tunjangan Lainnya',
            'component_type' => 'earning',
            'amount' => 350000,
            'calculation_method' => 'flat',
            'sort_order' => 20,
        ]);

        $this->actingAs($admin)->post(route('payroll.store'), [
            'employee_id' => $employee->id,
            'deduction_rule_id' => $rule->id,
            'monthly_salary' => 7000000,
            'period_start' => '2026-05-01',
            'period_end' => '2026-05-31',
        ])->assertRedirect(route('payroll.index'));

        $payroll = Payroll::where('employee_id', $employee->id)->firstOrFail();

        $this->assertSame('2500000.00', $payroll->allowance_thr);
        $this->assertSame('350000.00', $payroll->allowance_other);
        $this->assertDatabaseHas('payroll_items', [
            'payroll_id' => $payroll->id,
            'component_code' => 'thr_allowance',
            'component_type' => 'earning',
            'amount' => '2500000.00',
        ]);
        $this->assertDatabaseHas('payroll_items', [
            'payroll_id' => $payroll->id,
            'component_code' => 'other_allowance',
            'component_type' => 'earning',
            'amount' => '350000.00',
        ]);
    }

    protected function makePayrollContext(string $suffix): array
    {
        $tenant = Tenant::create([
            'name' => 'Payroll Policy '.$suffix,
            'slug' => 'payroll-policy-'.$suffix,
            'domain' => 'payroll-policy-'.$suffix.'.test',
            'status' => 'active',
        ]);

        $adminRole = Role::where('name', 'Admin HR')->firstOrFail();
        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Payroll Policy Admin '.$suffix,
            'email' => 'payroll-policy-admin-'.$suffix.'@example.test',
            'password' => 'password',
            'role_id' => $adminRole->id,
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $rule = DeductionRule::create([
            'tenant_id' => $tenant->id,
            'working_hours_per_day' => 8,
            'working_days_per_month' => 22,
            'tolerance_minutes' => 15,
            'rate_type' => 'proportional',
            'alpha_full_day' => true,
            'salary_type' => 'monthly',
        ]);

        return [$tenant, $admin, $rule];
    }

    protected function makeEmployee(Tenant $tenant, string $code, string $employmentType): Employee
    {
        return Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => $code,
            'name' => 'Payroll Policy Employee '.$code,
            'email' => strtolower($code).'@example.test',
            'status' => 'active',
            'employment_type' => $employmentType,
            'role' => 'staff',
        ]);
    }
}
