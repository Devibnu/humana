<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Role;
use App\Models\RolePermission;

class PayrollDeductionUITest extends TestCase
{
    use RefreshDatabase;

    public function test_create_payroll_form_shows_deduction_fields()
    {
        // Buat role dan permission untuk akses payroll
        $role = Role::firstOrCreate(['name' => 'Admin HR']);
        RolePermission::firstOrCreate(['role_id' => $role->id, 'menu_key' => 'payroll'], ['can_access' => true]);

        // Login sebagai admin HR
        $user = User::factory()->create(['role_id' => $role->id]);
        $this->actingAs($user);

        // Buat karyawan agar form tampil
        \App\Models\Employee::create([
            'tenant_id' => $user->tenant_id,
            'employee_code' => 'EMP002',
            'name' => 'Test Employee 2',
            'email' => 'employee2@example.test',
        ]);

        \Illuminate\Support\Facades\Artisan::call('view:clear');

        $response = $this->get('/payroll/create');
        $response->assertStatus(200);
        $response->assertSee('Potongan Pajak');
        $response->assertSee('Potongan BPJS');
        $response->assertSee('Potongan Pinjaman');
        $response->assertSee('name="deduction_tax"', false);
        $response->assertSee('name="deduction_bpjs"', false);
        $response->assertSee('name="deduction_loan"', false);
    }
}
