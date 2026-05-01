<?php

namespace Tests\Feature;

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\MenuAccessMiddleware;
use App\Http\Middleware\PermissionMiddleware;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollIndexUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            Authenticate::class,
            MenuAccessMiddleware::class,
            PermissionMiddleware::class,
        ]);
    }

    public function test_payroll_index_menampilkan_ringkasan_dan_filter_seperti_halaman_positions(): void
    {
        $user = User::factory()->create([
            'role' => 'admin_hr',
        ]);

        $tenant = Tenant::create([
            'name' => 'Tenant Payroll Alpha',
            'code' => 'TPA',
            'slug' => 'tenant-payroll-alpha',
            'domain' => 'tenant-payroll-alpha.test',
            'status' => 'active',
        ]);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'PAY-001',
            'name' => 'Sinta Payroll',
            'email' => 'sinta-payroll@example.test',
            'phone' => '081200000001',
            'status' => 'active',
        ]);

        Payroll::create([
            'employee_id' => $employee->id,
            'monthly_salary' => 9000000,
            'daily_wage' => 400000,
            'allowance_transport' => 300000,
            'allowance_meal' => 250000,
            'allowance_health' => 150000,
            'overtime_pay' => 500000,
            'deduction_tax' => 250000,
            'deduction_bpjs' => 125000,
            'deduction_loan' => 100000,
            'deduction_attendance' => 50000,
            'period_start' => '2026-04-01',
            'period_end' => '2026-04-30',
        ]);

        $response = $this->actingAs($user)->get(route('payroll.index'));

        $response->assertOk();
        $response->assertSee('data-testid="payroll-summary-total"', false);
        $response->assertSee('data-testid="payroll-summary-monthly-salary"', false);
        $response->assertSee('data-testid="payroll-summary-overtime"', false);
        $response->assertSee('data-testid="payroll-summary-deductions"', false);
        $response->assertSee('data-testid="payroll-filter-form"', false);
        $response->assertSee('data-testid="payroll-table"', false);
        $response->assertSee('data-testid="btn-open-payroll-reports"', false);
        $response->assertSee('Akumulasi Gaji Bulanan');
        $response->assertSee('Total Potongan');
        $response->assertSee('Sinta Payroll');
        $response->assertSee('Tenant Payroll Alpha');
    }

    public function test_payroll_index_bisa_filter_berdasarkan_pencarian_dan_tenant(): void
    {
        $user = User::factory()->create([
            'role' => 'admin_hr',
        ]);

        $tenantAlpha = Tenant::create([
            'name' => 'Tenant Alpha',
            'code' => 'TAL',
            'slug' => 'tenant-alpha',
            'domain' => 'tenant-alpha.test',
            'status' => 'active',
        ]);

        $tenantBeta = Tenant::create([
            'name' => 'Tenant Beta',
            'code' => 'TBE',
            'slug' => 'tenant-beta',
            'domain' => 'tenant-beta.test',
            'status' => 'active',
        ]);

        $employeeAlpha = Employee::create([
            'tenant_id' => $tenantAlpha->id,
            'employee_code' => 'PAY-A1',
            'name' => 'Sinta Filter',
            'email' => 'sinta-filter@example.test',
            'phone' => '081200000010',
            'status' => 'active',
        ]);

        $employeeBeta = Employee::create([
            'tenant_id' => $tenantBeta->id,
            'employee_code' => 'PAY-B1',
            'name' => 'Budi Filter',
            'email' => 'budi-filter@example.test',
            'phone' => '081200000011',
            'status' => 'active',
        ]);

        Payroll::create([
            'employee_id' => $employeeAlpha->id,
            'monthly_salary' => 8500000,
            'overtime_pay' => 300000,
            'period_start' => '2026-04-01',
            'period_end' => '2026-04-30',
        ]);

        Payroll::create([
            'employee_id' => $employeeBeta->id,
            'monthly_salary' => 7800000,
            'overtime_pay' => 150000,
            'period_start' => '2026-04-01',
            'period_end' => '2026-04-30',
        ]);

        $response = $this->actingAs($user)->get(route('payroll.index', [
            'search' => 'Sinta',
            'tenant_id' => $tenantAlpha->id,
            'sort_by' => 'employee_name',
            'sort_direction' => 'asc',
        ]));

        $response->assertOk();
        $response->assertSee('Filter aktif');
        $response->assertSee('Pencarian: Sinta');
        $response->assertSee('Tenant: Tenant Alpha');
        $response->assertSee('Urutan: Nama Karyawan (ASC)');
        $response->assertSee('Sinta Filter');
        $response->assertDontSee('Budi Filter');
    }
}