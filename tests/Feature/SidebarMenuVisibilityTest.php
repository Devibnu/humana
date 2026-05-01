<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarMenuVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
    }

    public function test_admin_hr_melihat_semua_menu_lembur_operasional(): void
    {
        $tenant = $this->makeTenant('sidebar-lembur-admin');
        $admin = $this->makeUser($tenant, 'admin_hr', 'admin-hr@sidebar.test', 'Admin HR');

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Data Master', false);
        $response->assertSee('Operasional Absensi', false);
        $response->assertSee('Operasional Lembur', false);
        $response->assertSee('Operasional Payroll', false);
        $response->assertSee('Jenis Cuti', false);
        $response->assertSee('Potongan', false);
        $response->assertSee('Laporan Cuti / Izin', false);
        $response->assertSee('Pengajuan Lembur', false);
        $response->assertSee('Persetujuan Lembur', false);
        $response->assertSee('Laporan Lembur', false);
        $response->assertSee('Payroll', false);
        $response->assertSee('Laporan Payroll', false);
    }

    public function test_manager_melihat_pengajuan_dan_persetujuan_tanpa_laporan(): void
    {
        $tenant = $this->makeTenant('sidebar-lembur-manager');
        $manager = $this->makeUser($tenant, 'manager', 'manager@sidebar.test', 'Manager');

        $response = $this->actingAs($manager)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Data Master', false);
        $response->assertSee('Operasional Absensi', false);
        $response->assertSee('Operasional Lembur', false);
        $response->assertDontSee('Operasional Payroll', false);
        $response->assertSee('Jenis Cuti', false);
        $response->assertSee('Laporan Cuti / Izin', false);
        $response->assertSee('Pengajuan Lembur', false);
        $response->assertSee('Persetujuan Lembur', false);
        $response->assertDontSee('Laporan Lembur', false);
        $response->assertDontSee('Payroll', false);
        $response->assertDontSee('Potongan', false);
    }

    public function test_karyawan_hanya_melihat_pengajuan_lembur(): void
    {
        $tenant = $this->makeTenant('sidebar-lembur-employee');
        $employee = $this->makeUser($tenant, 'employee', 'employee@sidebar.test', 'Karyawan');

        $response = $this->actingAs($employee)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('Data Master', false);
        $response->assertDontSee('Operasional Absensi', false);
        $response->assertSee('Operasional Lembur', false);
        $response->assertDontSee('Operasional Payroll', false);
        $response->assertSee('Pengajuan Lembur', false);
        $response->assertDontSee('Absensi Karyawan', false);
        $response->assertDontSee('Cuti / Izin', false);
        $response->assertDontSee('Jenis Cuti', false);
        $response->assertDontSee('Laporan Cuti / Izin', false);
        $response->assertDontSee('Persetujuan Lembur', false);
        $response->assertDontSee('Laporan Lembur', false);
        $response->assertDontSee('Payroll', false);
        $response->assertDontSee('Potongan', false);
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
}
