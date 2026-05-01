<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Role;
use App\Models\RolePermission;

class PayrollAllowanceUITest extends TestCase
{
    use RefreshDatabase;

    public function test_create_payroll_form_shows_allowance_fields()
    {
        // Buat role dan permission yang memberi akses ke menu payroll
        $role = Role::firstOrCreate(['name' => 'Admin HR']);
        RolePermission::firstOrCreate([
            'role_id' => $role->id,
            'menu_key' => 'payroll',
        ], [
            'can_access' => true,
        ]);

        // Login sebagai admin HR
        $user = User::factory()->create([
            'role_id' => $role->id,
        ]);
        $this->actingAs($user);

        // Buat satu karyawan supaya form payroll tampil
        \App\Models\Employee::create([
            'tenant_id' => $user->tenant_id,
            'employee_code' => 'EMP001',
            'name' => 'Test Employee',
            'email' => 'employee@example.test',
        ]);

        // Pastikan compiled views bersih agar perubahan partial Blade terpakai
        \Illuminate\Support\Facades\Artisan::call('view:clear');

        // Akses halaman create payroll
        $response = $this->get('/payroll/create');

        // Pastikan halaman terbuka
        $response->assertStatus(200);

        // (no debug output)

        // Cek field tunjangan muncul
        $response->assertSee('Tunjangan Transport');
        $response->assertSee('Tunjangan Makan');
        $response->assertSee('Tunjangan Kesehatan');

        // Cek input name ada
        $response->assertSee('name="allowance_transport"', false);
        $response->assertSee('name="allowance_meal"', false);
        $response->assertSee('name="allowance_health"', false);
    }
}
