<?php

namespace Tests\Feature;

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\MenuAccessMiddleware;
use App\Http\Middleware\PermissionMiddleware;
use App\Models\Employee;
use App\Models\Lembur;
use App\Models\LemburSetting;
use App\Models\Payroll;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LemburEdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesTableSeeder::class);
    }

    public function test_slip_gaji_menampilkan_semua_lembur_dalam_periode(): void
    {
        $this->withoutMiddleware([
            Authenticate::class,
            MenuAccessMiddleware::class,
            PermissionMiddleware::class,
        ]);

        $tenant = $this->makeTenant('edge-slip-js');
        $admin = $this->makeUser($tenant, 'admin-edge@humana.test', 'admin_hr');
        $employee = $this->makeEmployee($tenant, 'EMP-EDGE', 'Budi', 'budi-edge@humana.test');

        LemburSetting::create([
            'tenant_id' => $tenant->id,
            'role_pengaju' => 'karyawan',
            'butuh_persetujuan' => true,
            'tipe_tarif' => 'per_jam',
            'nilai_tarif' => 50000,
            'multiplier' => 1.5,
        ]);

        Lembur::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'waktu_mulai' => '2026-05-01 18:00:00',
            'waktu_selesai' => '2026-05-01 20:00:00',
            'durasi_jam' => 2,
            'status' => 'disetujui',
        ]);

        Lembur::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'waktu_mulai' => '2026-05-10 19:00:00',
            'waktu_selesai' => '2026-05-10 22:00:00',
            'durasi_jam' => 3,
            'status' => 'disetujui',
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
        $response->assertSee('Total Jam: 5');
        $response->assertSee('Total Nilai: Rp 250,000');
    }

    public function test_export_lembur_empty_state_tetap_valid(): void
    {
        $tenant = $this->makeTenant('edge-export-js');
        $user = $this->makeUser($tenant, 'admin-export@humana.test', 'admin_hr');

        $response = $this->actingAs($user)->get(route('lembur.export'));

        $response->assertStatus(200);
        $response->assertHeader('content-disposition', 'attachment; filename=lembur-report-waktu-mulai-desc.xlsx');
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

    private function makeUser(Tenant $tenant, string $email, string $role): User
    {
        return User::create([
            'tenant_id' => $tenant->id,
            'role_id' => Role::idForSystemKey($role),
            'name' => ucfirst($role),
            'email' => $email,
            'password' => bcrypt('password'),
            'role' => $role,
            'status' => 'active',
        ]);
    }

    private function makeEmployee(Tenant $tenant, string $code, string $name, string $email): Employee
    {
        return Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => $code,
            'name' => $name,
            'email' => $email,
            'phone' => '081234567890',
            'status' => 'active',
        ]);
    }
}
