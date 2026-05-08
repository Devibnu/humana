<?php

namespace Tests\Feature;

use App\Models\PayrollPolicy;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollPolicyUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_payroll_policy_index_and_create_form(): void
    {
        [$tenant, $admin] = $this->makeTenantAndAdmin('ui-open');

        $this->actingAs($admin)
            ->get(route('payroll.policies.index', ['tenant_id' => $tenant->id]))
            ->assertOk()
            ->assertSee('Payroll Policy')
            ->assertSee('Tambah Policy');

        $this->actingAs($admin)
            ->get(route('payroll.policies.create', ['tenant_id' => $tenant->id]))
            ->assertOk()
            ->assertSee('Tambah Payroll Policy')
            ->assertSee('Uang Makan')
            ->assertSee('Saat Cuti Dibayar');
    }

    public function test_admin_can_store_payroll_policy_with_components(): void
    {
        [$tenant, $admin] = $this->makeTenantAndAdmin('ui-store');

        $this->actingAs($admin)
            ->post(route('payroll.policies.store'), [
                'tenant_id' => $tenant->id,
                'name' => 'Tetap - Uang Makan Dipotong',
                'status' => 'active',
                'employment_type' => 'tetap',
                'priority' => 10,
                'components' => [
                    [
                        'component_code' => 'meal_allowance',
                        'component_name' => 'Uang Makan',
                        'component_type' => 'earning',
                        'amount' => 25000,
                        'calculation_method' => 'daily_attendance',
                        'leave_paid_behavior' => 'deduct_daily',
                        'leave_unpaid_behavior' => 'deduct_daily',
                        'sort_order' => 10,
                    ],
                ],
            ])
            ->assertRedirect(route('payroll.policies.index', ['tenant_id' => $tenant->id]));

        $policy = PayrollPolicy::where('tenant_id', $tenant->id)->firstOrFail();
        $this->assertSame('tetap', $policy->employment_type);
        $this->assertDatabaseHas('payroll_policy_components', [
            'payroll_policy_id' => $policy->id,
            'component_code' => 'meal_allowance',
            'leave_paid_behavior' => 'deduct_daily',
        ]);
    }

    protected function makeTenantAndAdmin(string $suffix): array
    {
        $this->seed(RolesTableSeeder::class);

        $tenant = Tenant::create([
            'name' => 'Policy UI '.$suffix,
            'slug' => 'policy-ui-'.$suffix,
            'domain' => 'policy-ui-'.$suffix.'.test',
            'status' => 'active',
        ]);

        $adminRole = Role::where('name', 'Admin HR')->firstOrFail();
        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Policy UI Admin '.$suffix,
            'email' => 'policy-ui-admin-'.$suffix.'@example.test',
            'password' => 'password',
            'role_id' => $adminRole->id,
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        return [$tenant, $admin];
    }
}
