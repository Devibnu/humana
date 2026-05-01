<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Position;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PositionsDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_detail_posisi_menampilkan_kartu_ringkas_seperti_halaman_departemen(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Detail Posisi',
            'code' => 'TEN-DET-POS',
            'slug' => 'tenant-detail-posisi',
            'domain' => 'tenant-detail-posisi.test',
            'status' => 'active',
        ]);

        $department = Department::create([
            'tenant_id' => $tenant->id,
            'name' => 'Information Technology',
            'code' => 'IT',
            'description' => 'Mengelola pengembangan sistem internal.',
            'status' => 'active',
        ]);

        $position = Position::create([
            'tenant_id' => $tenant->id,
            'department_id' => $department->id,
            'name' => 'Software Engineer',
            'code' => 'SE-01',
            'description' => 'Mengembangkan aplikasi internal perusahaan.',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Detail Posisi',
            'email' => 'admin-detail-posisi@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('positions.show', $position));

        $response->assertOk();
        $response->assertSee('Detail Posisi');
        $response->assertSee('Software Engineer');
        $response->assertSee('SE-01');
        $response->assertSee('Tenant Detail Posisi');
        $response->assertSee('Information Technology');
        $response->assertSee('Jumlah Karyawan:');
        $response->assertSee('Aktif');
        $response->assertSee('Mengembangkan aplikasi internal perusahaan.');
        $response->assertDontSee('Belum ada deskripsi posisi.');
        $response->assertSee('fas fa-arrow-left me-1', false);
    }

    public function test_detail_posisi_menampilkan_placeholder_deskripsi_jika_kosong(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Detail Placeholder Posisi',
            'code' => 'TEN-DET-POS-EMP',
            'slug' => 'tenant-detail-placeholder-posisi',
            'domain' => 'tenant-detail-placeholder-posisi.test',
            'status' => 'active',
        ]);

        $department = Department::create([
            'tenant_id' => $tenant->id,
            'name' => 'Finance',
            'code' => 'FIN',
            'status' => 'active',
        ]);

        $position = Position::create([
            'tenant_id' => $tenant->id,
            'department_id' => $department->id,
            'name' => 'Finance Staff',
            'code' => 'FIN-02',
            'description' => null,
            'status' => 'inactive',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Detail Placeholder Posisi',
            'email' => 'admin-detail-placeholder-posisi@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('positions.show', $position));

        $response->assertOk();
        $response->assertSee('Finance Staff');
        $response->assertSee('Nonaktif');
        $response->assertSee('Belum ada deskripsi posisi.');
    }
}