<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarStructureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
    }

    public function test_sidebar_mengelompokkan_menu_master_dan_operasional_secara_rapi(): void
    {
        $tenant = Tenant::create([
            'name' => 'SIDEBAR STRUCTURE',
            'code' => 'SDS' . substr(strtoupper(dechex(crc32('sidebar-structure'))), 0, 3),
            'slug' => 'sidebar-structure',
            'domain' => 'sidebar-structure.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'role_id' => Role::idForSystemKey('admin_hr'),
            'role' => 'admin_hr',
            'name' => 'Admin HR',
            'email' => 'admin-structure@humana.test',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertOk();
        $response->assertSeeInOrder([
            'Data Master',
            'Pengguna',
            'Karyawan',
            'Departemen',
            'Posisi',
            'Lokasi Kerja',
            'Tenant',
            'Role',
            'Jenis Cuti',
            'Potongan',
            'Operasional Absensi',
            'Absensi Karyawan',
            'Cuti / Izin',
            'Laporan Cuti / Izin',
            'Operasional Lembur',
            'Pengajuan Lembur',
            'Persetujuan Lembur',
            'Laporan Lembur',
            'Operasional Payroll',
            'Payroll',
            'Laporan Payroll',
        ], false);
    }
}
