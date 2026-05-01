<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Position;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PositionsUiLanguageTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $admin;
    private Position $position;
    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Tenant UI Posisi',
            'code' => 'TEN-UI-POS',
            'slug' => 'tenant-ui-posisi',
            'domain' => 'tenant-ui-posisi.test',
            'status' => 'active',
        ]);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin UI Posisi',
            'email' => 'admin-ui-posisi@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $this->department = Department::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Gudang',
            'code' => 'GDG',
            'description' => 'Departemen gudang utama.',
            'status' => 'active',
        ]);

        $this->position = Position::create([
            'tenant_id' => $this->tenant->id,
            'department_id' => $this->department->id,
            'name' => 'Supervisor Gudang',
            'code' => 'MGR-01',
            'description' => 'Mengawasi alur kerja gudang.',
            'status' => 'active',
        ]);
    }

    public function test_halaman_tambah_posisi_menggunakan_bahasa_indonesia(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('positions.create'));

        $response->assertOk();
        $response->assertSee('Tambah Posisi');
        $response->assertSee('Nama Posisi');
        $response->assertSee('Kode Posisi');
        $response->assertSee('Deskripsi');
        $response->assertSee('Contoh: MGR-01');
        $response->assertSee('Gunakan nama resmi sesuai struktur organisasi');
        $response->assertSee('Opsional, untuk kode internal');
        $response->assertSee('Batal');
        $response->assertSee('Simpan');
        $response->assertSee('fas fa-save me-1', false);
        $response->assertSee('fas fa-times me-1', false);
    }

    public function test_halaman_edit_posisi_menggunakan_bahasa_indonesia(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('positions.edit', $this->position));

        $response->assertOk();
        $response->assertSee('Edit Posisi');
        $response->assertSee('Nama Posisi');
        $response->assertSee('Kode Posisi');
        $response->assertSee('Deskripsi');
        $response->assertSee('Contoh: MGR-01');
        $response->assertSee('Gunakan nama resmi sesuai struktur organisasi');
        $response->assertSee('Opsional, untuk kode internal');
        $response->assertSee('Batal');
        $response->assertSee('Simpan');
        $response->assertSee('value="Supervisor Gudang"', false);
        $response->assertSee('value="MGR-01"', false);
        $response->assertSee('Mengawasi alur kerja gudang.');
    }
}