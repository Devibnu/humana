<?php

namespace Tests\Feature;

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\MenuAccessMiddleware;
use App\Http\Middleware\PermissionMiddleware;
use App\Models\DeductionRule;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollCrudFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_payroll_crud_flow(): void
    {
        $this->withoutMiddleware([
            Authenticate::class,
            MenuAccessMiddleware::class,
            PermissionMiddleware::class,
        ]);

        $tenant = Tenant::create([
            'name' => 'Tenant Payroll',
            'code' => 'TP-01',
            'slug' => 'tenant-payroll',
            'domain' => 'tenant-payroll.test',
            'status' => 'active',
        ]);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'EMP-001',
            'name' => 'Karyawan Payroll',
            'email' => 'karyawan-payroll@example.test',
            'phone' => '081234567890',
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

        $response = $this->post(route('payroll.store'), [
            'employee_id' => $employee->id,
            'deduction_rule_id' => $rule->id,
            'monthly_salary' => 7500000,
            'daily_wage' => 300000,
            'period_start' => '2026-04-01',
            'period_end' => '2026-04-30',
        ]);

        $response->assertRedirect(route('payroll.index'));
        $this->assertDatabaseHas('payrolls', [
            'employee_id' => $employee->id,
            'monthly_salary' => 7500000,
            'daily_wage' => 300000,
            'period_start' => '2026-04-01',
            'period_end' => '2026-04-30',
        ]);

        $payroll = Payroll::query()->firstOrFail();

        $response = $this->put(route('payroll.update', $payroll), [
            'employee_id' => $employee->id,
            'deduction_rule_id' => $rule->id,
            'monthly_salary' => 8000000,
            'daily_wage' => 350000,
            'period_start' => '2026-04-01',
            'period_end' => '2026-04-30',
        ]);

        $response->assertRedirect(route('payroll.index'));
        $this->assertDatabaseHas('payrolls', [
            'id' => $payroll->id,
            'monthly_salary' => 8000000,
            'daily_wage' => 350000,
        ]);

        $response = $this->delete(route('payroll.destroy', $payroll));

        $response->assertRedirect(route('payroll.index'));
        $this->assertDatabaseMissing('payrolls', [
            'id' => $payroll->id,
        ]);
    }
}
