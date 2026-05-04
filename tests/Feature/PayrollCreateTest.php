<?php

namespace Tests\Feature;

use App\Models\DeductionRule;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_hr_yang_mengakses_input_payroll_diarahkan_ke_generate_payroll(): void
    {
        $this->seed(RolesTableSeeder::class);

        $tenant = Tenant::create([
            'name' => 'Tenant Payroll Create',
            'code' => 'TEN-PAY-001',
            'slug' => 'tenant-payroll-create',
            'domain' => 'tenant-payroll-create.test',
            'status' => 'active',
        ]);

        $adminRole = Role::where('name', 'Admin HR')->firstOrFail();

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Payroll Create',
            'email' => 'admin-payroll-create@example.test',
            'password' => 'password',
            'role_id' => $adminRole->id,
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('payroll.create'));

        $response->assertRedirect(route('payroll.generate'));
        $response->assertSessionHas('info', 'Input payroll manual sudah tidak digunakan. Gunakan Generate Payroll.');
    }

    public function test_submit_payroll_divalidasi_dan_menampilkan_pesan_informatif(): void
    {
        $this->seed(RolesTableSeeder::class);

        $tenant = Tenant::create([
            'name' => 'Tenant Payroll Submit',
            'code' => 'TEN-PAY-002',
            'slug' => 'tenant-payroll-submit',
            'domain' => 'tenant-payroll-submit.test',
            'status' => 'active',
        ]);

        $adminRole = Role::where('name', 'Admin HR')->firstOrFail();

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Payroll Submit',
            'email' => 'admin-payroll-submit@example.test',
            'password' => 'password',
            'role_id' => $adminRole->id,
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'PAY-002',
            'name' => 'Karyawan Submit Payroll',
            'email' => 'karyawan-submit-payroll@example.test',
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

        $response = $this->actingAs($admin)->post(route('payroll.store'), [
            'employee_id' => $employee->id,
            'deduction_rule_id' => $rule->id,
            'monthly_salary' => 7500000,
            'daily_wage' => null,
            'period_start' => '2026-04-01',
            'period_end' => '2026-04-30',
        ]);

        $response->assertRedirect(route('payroll.index'));
        $response->assertSessionHas('success', 'Payroll berhasil disimpan dengan aturan potongan terpilih');

        $this->assertDatabaseHas('payrolls', [
            'employee_id' => $employee->id,
            'deduction_rule_id' => $rule->id,
            'monthly_salary' => 7500000,
            'period_start' => '2026-04-01',
            'period_end' => '2026-04-30',
        ]);

        $payroll = Payroll::query()->first();
        $this->assertNotNull($payroll);
    }

    public function test_admin_hr_dapat_membuka_form_edit_payroll_dengan_struktur_baru(): void
    {
        $this->seed(RolesTableSeeder::class);

        $tenant = Tenant::create([
            'name' => 'Tenant Payroll Edit',
            'code' => 'TEN-PAY-003',
            'slug' => 'tenant-payroll-edit',
            'domain' => 'tenant-payroll-edit.test',
            'status' => 'active',
        ]);

        $adminRole = Role::where('name', 'Admin HR')->firstOrFail();

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Payroll Edit',
            'email' => 'admin-payroll-edit@example.test',
            'password' => 'password',
            'role_id' => $adminRole->id,
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'PAY-003',
            'name' => 'Karyawan Edit Payroll',
            'email' => 'karyawan-edit-payroll@example.test',
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

        $payroll = Payroll::create([
            'employee_id' => $employee->id,
            'deduction_rule_id' => $rule->id,
            'monthly_salary' => 8200000,
            'period_start' => '2026-04-01',
            'period_end' => '2026-04-30',
        ]);

        $response = $this->actingAs($admin)->get(route('payroll.edit', $payroll));

        $response->assertOk();
        $response->assertSee('Edit Payroll');
        $response->assertSee('data-testid="payroll-edit-card"', false);
        $response->assertSee('data-testid="payroll-edit-form"', false);
        $response->assertSee('data-testid="payroll-form-context-card"', false);
        $response->assertSee('data-testid="payroll-form-guidance-card"', false);
        $response->assertSee('Karyawan Edit Payroll');
        $response->assertSee('Checklist cepat sebelum simpan');
    }
}