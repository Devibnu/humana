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

class PayrollReportsAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
    }

    public function test_admin_hr_can_access_payroll_reports_route_and_sidebar_menu(): void
    {
        $tenant = $this->makeTenant('payroll-reports-admin');
        $admin = $this->makeUser($tenant, 'admin_hr', 'payroll-reports-admin@test.local', 'Admin HR');
        $employee = $this->makeEmployee($tenant, 'PAYR-001', 'Karyawan Payroll Admin', 'karyawan-payroll-admin@test.local');
        $this->makePayroll($employee, [
            'period_start' => '2026-04-01',
            'period_end' => '2026-04-30',
            'monthly_salary' => 5000000,
            'allowance_transport' => 300000,
            'allowance_meal' => 200000,
            'deduction_tax' => 150000,
        ]);

        $this->actingAs($admin)
            ->get(route('payroll.reports'))
            ->assertOk()
            ->assertSee('Laporan Payroll')
            ->assertSee('Karyawan Payroll Admin')
            ->assertSee('5.350.000');

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertSee('data-testid="sidebar-menu-payroll-reports"', false)
            ->assertSee(route('payroll.reports'), false);
    }

    public function test_manager_without_permission_gets_forbidden_for_payroll_reports(): void
    {
        $tenant = $this->makeTenant('payroll-reports-manager');
        $manager = $this->makeUser($tenant, 'manager', 'payroll-reports-manager@test.local', 'Manager');

        $this->actingAs($manager)
            ->get(route('payroll.reports'))
            ->assertForbidden();
    }

    public function test_payroll_reports_can_filter_by_date_and_tenant(): void
    {
        $tenantA = $this->makeTenant('payroll-filter-alpha');
        $tenantB = $this->makeTenant('payroll-filter-beta');
        $admin = $this->makeUser($tenantA, 'admin_hr', 'payroll-reports-filter@test.local', 'Admin HR Filter');

        $employeeA = $this->makeEmployee($tenantA, 'PAYR-101', 'Pegawai Alpha', 'pegawai-alpha-payroll@test.local');
        $employeeB = $this->makeEmployee($tenantB, 'PAYR-102', 'Pegawai Beta', 'pegawai-beta-payroll@test.local');

        $this->makePayroll($employeeA, [
            'period_start' => '2026-04-01',
            'period_end' => '2026-04-30',
            'monthly_salary' => 7000000,
        ]);

        $this->makePayroll($employeeB, [
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-31',
            'monthly_salary' => 6500000,
        ]);

        $this->actingAs($admin)
            ->get(route('payroll.reports', [
                'start_date' => '2026-04-01',
                'end_date' => '2026-04-30',
                'tenant_id' => $tenantA->id,
            ]))
            ->assertOk()
            ->assertSee('Pegawai Alpha')
            ->assertDontSee('Pegawai Beta');
    }

    public function test_payroll_reports_can_filter_by_employee_name_and_show_kpis(): void
    {
        $tenantA = $this->makeTenant('payroll-kpi-alpha');
        $tenantB = $this->makeTenant('payroll-kpi-beta');
        $admin = $this->makeUser($tenantA, 'admin_hr', 'payroll-reports-kpi@test.local', 'Admin HR KPI');

        $employeeA = $this->makeEmployee($tenantA, 'PAYR-301', 'Rina Payroll', 'rina-payroll@test.local');
        $employeeB = $this->makeEmployee($tenantB, 'PAYR-302', 'Budi Payroll', 'budi-payroll@test.local');

        $this->makePayroll($employeeA, [
            'monthly_salary' => 3000000,
            'allowance_transport' => 200000,
            'deduction_tax' => 100000,
        ]);

        $this->makePayroll($employeeB, [
            'monthly_salary' => 6000000,
            'allowance_transport' => 500000,
            'deduction_tax' => 250000,
        ]);

        $this->actingAs($admin)
            ->get(route('payroll.reports', ['employee_name' => 'Rina']))
            ->assertOk()
            ->assertSee('Rina Payroll')
            ->assertDontSee('Budi Payroll')
            ->assertSee('data-testid="payroll-reports-kpi-total-data">1<', false)
            ->assertSee('data-testid="payroll-reports-kpi-average-paid">Rp 3.100.000<', false)
            ->assertSee('data-testid="payroll-reports-kpi-total-deduction">Rp 100.000<', false)
            ->assertSee('Rp 3.100.000');
    }

    public function test_payroll_reports_can_sort_by_employee_name_and_show_pagination_summary(): void
    {
        $tenant = $this->makeTenant('payroll-sort-alpha');
        $admin = $this->makeUser($tenant, 'admin_hr', 'payroll-reports-sort@test.local', 'Admin HR Sort');

        $zaki = $this->makeEmployee($tenant, 'PAYR-401', 'Zaki Payroll', 'zaki-payroll@test.local');
        $andi = $this->makeEmployee($tenant, 'PAYR-402', 'Andi Payroll', 'andi-payroll@test.local');

        $this->makePayroll($zaki, ['monthly_salary' => 4500000]);
        $this->makePayroll($andi, ['monthly_salary' => 3500000]);

        $response = $this->actingAs($admin)
            ->get(route('payroll.reports', ['sort_by' => 'employee_name', 'sort_order' => 'asc']));

        $response->assertOk();
        $response->assertSee('data-testid="payroll-reports-pagination-summary">', false);
        $response->assertSee('data-testid="payroll-reports-sort-employee-name">▲<', false);
        $response->assertSee('data-testid="payroll-reports-header-employee-name"', false);
        $response->assertSee('bg-primary text-white', false);

        $content = $response->getContent();
        $this->assertNotFalse($content);
        $this->assertLessThan(
            strpos($content, 'Zaki Payroll'),
            strpos($content, 'Andi Payroll')
        );
    }

    public function test_payroll_reports_can_change_per_page_size(): void
    {
        $tenant = $this->makeTenant('payroll-per-page-alpha');
        $admin = $this->makeUser($tenant, 'admin_hr', 'payroll-reports-per-page@test.local', 'Admin HR Per Page');

        for ($index = 1; $index <= 12; $index++) {
            $employee = $this->makeEmployee(
                $tenant,
                'PAYR-5'.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                'Pegawai Per Page '.$index,
                'pegawai-per-page-'.$index.'@test.local'
            );

            $this->makePayroll($employee, [
                'monthly_salary' => 3000000 + $index,
            ]);
        }

        $response = $this->actingAs($admin)
            ->get(route('payroll.reports', ['per_page' => 25]));

        $response->assertOk();
        $response->assertSee('data-testid="payroll-reports-per-page-select"', false);
        $response->assertSee('Menampilkan 1-12 dari 12 data');
    }

    public function test_admin_hr_can_export_payroll_reports_to_excel_and_pdf(): void
    {
        $tenant = $this->makeTenant('payroll-export-alpha');
        $admin = $this->makeUser($tenant, 'admin_hr', 'payroll-reports-export@test.local', 'Admin HR Export');
        $employee = $this->makeEmployee($tenant, 'PAYR-201', 'Pegawai Export', 'pegawai-export-payroll@test.local');

        $this->makePayroll($employee, [
            'period_start' => '2026-04-01',
            'period_end' => '2026-04-30',
            'monthly_salary' => 4000000,
        ]);

        $this->actingAs($admin)
            ->get(route('payroll.reports.export', ['format' => 'xlsx']))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($admin)
            ->get(route('payroll.reports.export', ['format' => 'pdf']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    private function makeTenant(string $slug): Tenant
    {
        $sanitized = strtoupper(preg_replace('/[^A-Z0-9]/', '', strtoupper($slug)) ?? '');
        $code = substr($sanitized, 0, 5) . substr(strtoupper(dechex(crc32($slug))), 0, 3);

        return Tenant::create([
            'name' => strtoupper(str_replace('-', ' ', $slug)),
            'code' => $code,
            'slug' => $slug,
            'domain' => $slug . '.test',
            'status' => 'active',
        ]);
    }

    private function makeUser(Tenant $tenant, string $roleKey, string $email, string $name): User
    {
        return User::create([
            'tenant_id' => $tenant->id,
            'role_id' => Role::idForSystemKey($roleKey),
            'role' => $roleKey,
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
    }

    private function makeEmployee(Tenant $tenant, string $employeeCode, string $name, string $email): Employee
    {
        return Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => $employeeCode,
            'name' => $name,
            'email' => $email,
            'status' => 'active',
        ]);
    }

    private function makePayroll(Employee $employee, array $attributes = []): Payroll
    {
        return Payroll::create(array_merge([
            'employee_id' => $employee->id,
            'monthly_salary' => 0,
            'daily_wage' => null,
            'allowance_transport' => 0,
            'allowance_meal' => 0,
            'allowance_health' => 0,
            'overtime_pay' => 0,
            'deduction_tax' => 0,
            'deduction_bpjs' => 0,
            'deduction_loan' => 0,
            'deduction_attendance' => 0,
            'period_start' => '2026-04-01',
            'period_end' => '2026-04-30',
        ], $attributes));
    }
}