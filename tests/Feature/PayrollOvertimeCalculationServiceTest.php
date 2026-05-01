<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\DeductionRule;
use App\Models\Employee;
use App\Models\OvertimeRule;
use App\Models\Tenant;
use App\Services\PayrollOvertimeCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollOvertimeCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calculates_daily_overtime_using_the_explicit_salary_type(): void
    {
        [$tenant, $employee] = $this->makeEmployeeContext();

        OvertimeRule::create([
            'tenant_id' => $tenant->id,
            'salary_type' => 'daily',
            'standard_hours_per_day' => 8,
            'rate_first_hour' => 1.5,
            'rate_next_hours' => 2.0,
        ]);

        Attendance::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'date' => '2026-04-21',
            'check_in' => '08:00:00',
            'check_out' => '20:30:00',
            'status' => 'Hadir',
            'overtime_hours' => 2.5,
        ]);

        $result = app(PayrollOvertimeCalculationService::class)->calculate(
            $employee,
            '2026-04-01',
            '2026-04-30',
            null,
            400000,
            'daily',
        );

        $this->assertEquals(225000.0, $result['overtime_pay']);
        $this->assertStringContainsString('Lembur 2.5 jam', $result['overtime_note']);
    }

    public function test_it_calculates_monthly_overtime_using_tenant_working_days(): void
    {
        [$tenant, $employee] = $this->makeEmployeeContext();

        DeductionRule::create([
            'tenant_id' => $tenant->id,
            'salary_type' => 'monthly',
            'working_hours_per_day' => 8,
            'working_days_per_month' => 22,
            'tolerance_minutes' => 0,
            'rate_type' => 'flat',
            'alpha_full_day' => true,
        ]);

        OvertimeRule::create([
            'tenant_id' => $tenant->id,
            'salary_type' => 'monthly',
            'standard_hours_per_day' => 8,
            'rate_first_hour' => 1.5,
            'rate_next_hours' => 2.0,
        ]);

        Attendance::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'date' => '2026-04-22',
            'check_in' => '08:00:00',
            'check_out' => '18:00:00',
            'status' => 'Hadir',
            'overtime_hours' => 1.5,
        ]);

        $result = app(PayrollOvertimeCalculationService::class)->calculate(
            $employee,
            '2026-04-01',
            '2026-04-30',
            8800000,
            null,
            'monthly',
        );

        $this->assertEquals(125000.0, $result['overtime_pay']);
        $this->assertStringContainsString('Lembur 1.5 jam', $result['overtime_note']);
    }

    protected function makeEmployeeContext(): array
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Overtime Test',
            'code' => 'TEN-OT-001',
            'slug' => 'tenant-overtime-test',
            'domain' => 'tenant-overtime-test.test',
            'status' => 'active',
        ]);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'EMP-OT-001',
            'name' => 'Employee Overtime Test',
            'email' => 'employee-overtime-test@example.test',
            'status' => 'active',
        ]);

        return [$tenant, $employee];
    }
}
