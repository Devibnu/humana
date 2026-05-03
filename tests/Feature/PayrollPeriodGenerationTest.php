<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\DeductionRule;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollPeriod;
use App\Models\PayrollSetting;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollPeriodGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
    }

    public function test_admin_can_save_company_payroll_setting(): void
    {
        [$tenant, $admin] = $this->makeTenantAndAdmin('setting');

        $this->actingAs($admin)
            ->post(route('payroll.settings.update'), [
                'tenant_id' => $tenant->id,
                'payroll_day' => 27,
                'period_start_day' => 26,
                'period_end_day' => 25,
                'period_month_offset' => 'current',
                'publish_slips_on_approval' => '1',
                'status' => 'active',
            ])
            ->assertRedirect(route('payroll.settings'));

        $this->assertDatabaseHas('payroll_settings', [
            'tenant_id' => $tenant->id,
            'payroll_day' => 27,
            'period_start_day' => 26,
            'period_end_day' => 25,
            'publish_slips_on_approval' => true,
        ]);
    }

    public function test_generate_payroll_uses_company_period_and_latest_payroll_template(): void
    {
        [$tenant, $admin] = $this->makeTenantAndAdmin('generate');

        PayrollSetting::create([
            'tenant_id' => $tenant->id,
            'payroll_day' => 27,
            'period_start_day' => 26,
            'period_end_day' => 25,
            'period_month_offset' => 'current',
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

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'PAY-GEN-001',
            'name' => 'Generated Employee',
            'email' => 'generated@example.test',
            'status' => 'active',
        ]);

        Payroll::create([
            'employee_id' => $employee->id,
            'deduction_rule_id' => $rule->id,
            'monthly_salary' => 8800000,
            'allowance_transport' => 300000,
            'allowance_meal' => 250000,
            'allowance_health' => 200000,
            'deduction_tax' => 100000,
            'deduction_bpjs' => 150000,
            'deduction_loan' => 0,
            'period_start' => '2026-04-01',
            'period_end' => '2026-04-30',
        ]);

        Attendance::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'date' => '2026-04-30',
            'check_in' => '08:45',
            'check_out' => '16:30',
            'status' => 'late',
            'late_minutes' => 45,
            'early_leave_minutes' => 30,
        ]);

        $this->actingAs($admin)
            ->post(route('payroll.generate.store'), [
                'tenant_id' => $tenant->id,
                'payroll_month' => '2026-05',
            ])
            ->assertRedirect(route('payroll.index', ['tenant_id' => $tenant->id]));

        $period = PayrollPeriod::firstOrFail();
        $this->assertSame('2026-04-26', $period->period_start->toDateString());
        $this->assertSame('2026-05-25', $period->period_end->toDateString());
        $this->assertSame('2026-05-27', $period->payroll_date->toDateString());

        $generatedPayroll = Payroll::where('payroll_period_id', $period->id)->firstOrFail();
        $this->assertSame($employee->id, $generatedPayroll->employee_id);
        $this->assertSame('8800000.00', $generatedPayroll->monthly_salary);
        $this->assertGreaterThan(0, (float) $generatedPayroll->deduction_attendance);
        $this->assertStringContainsString('Pulang cepat', $generatedPayroll->deduction_attendance_note);
    }

    protected function makeTenantAndAdmin(string $suffix): array
    {
        $tenant = Tenant::create([
            'name' => 'Payroll Period '.$suffix,
            'slug' => 'payroll-period-'.$suffix,
            'domain' => 'payroll-period-'.$suffix.'.test',
            'status' => 'active',
        ]);

        $adminRole = Role::where('name', 'Admin HR')->firstOrFail();
        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Payroll Admin '.$suffix,
            'email' => 'payroll-admin-'.$suffix.'@example.test',
            'password' => 'password',
            'role_id' => $adminRole->id,
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        return [$tenant, $admin];
    }
}
