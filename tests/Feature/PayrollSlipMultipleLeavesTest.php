<?php

namespace Tests\Feature;

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\MenuAccessMiddleware;
use App\Http\Middleware\PermissionMiddleware;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Payroll;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollSlipMultipleLeavesTest extends TestCase
{
    use RefreshDatabase;

    public function test_payroll_slip_shows_all_leave_types_in_notes(): void
    {
        $this->withoutMiddleware([
            Authenticate::class,
            MenuAccessMiddleware::class,
            PermissionMiddleware::class,
        ]);

        $tenant = Tenant::create([
            'name' => 'JS',
            'code' => 'JS-01',
            'slug' => 'js',
            'domain' => 'js.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'name' => 'Admin HR',
            'email' => 'admin@humana.test',
            'password' => bcrypt('password'),
            'tenant_id' => $tenant->id,
            'role' => 'admin_hr',
        ]);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'EMP-BUDI',
            'name' => 'Budi',
            'email' => 'budi@js.test',
            'phone' => '081234567890',
            'status' => 'active',
        ]);

        $annual = LeaveType::create([
            'tenant_id' => $tenant->id,
            'name' => 'Cuti Tahunan',
        ]);

        $sick = LeaveType::create([
            'tenant_id' => $tenant->id,
            'name' => 'Cuti Sakit',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $annual->id,
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-02',
            'reason' => 'Liburan',
            'status' => 'approved',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $sick->id,
            'start_date' => '2026-05-10',
            'end_date' => '2026-05-11',
            'reason' => 'Sakit demam',
            'status' => 'approved',
        ]);

        $payroll = Payroll::create([
            'employee_id' => $employee->id,
            'monthly_salary' => 5000000,
            'daily_wage' => 200000,
            'period_start' => '2026-05-01',
            'period_end' => '2026-05-31',
        ]);

        $response = $this->actingAs($admin)->get(route('payroll.show', $payroll->id));

        $response->assertStatus(200);
        $response->assertSee('Cuti Tahunan');
        $response->assertSee('Cuti Sakit');
    }
}
