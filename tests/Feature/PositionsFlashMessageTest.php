<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Position;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PositionsFlashMessageTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $admin;
    private Department $department;
    private Position $position;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Tenant Flash Posisi',
            'code' => 'TEN-FLASH-POS',
            'slug' => 'tenant-flash-posisi',
            'domain' => 'tenant-flash-posisi.test',
            'status' => 'active',
        ]);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin Flash Posisi',
            'email' => 'admin-flash-posisi@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $this->department = Department::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Finance',
            'code' => 'FIN',
            'description' => 'Mengelola alur keuangan tenant.',
            'status' => 'active',
        ]);

        $this->position = Position::create([
            'tenant_id' => $this->tenant->id,
            'department_id' => $this->department->id,
            'name' => 'Supervisor Finance',
            'code' => 'FIN-01',
            'description' => 'Mengawasi proses keuangan.',
            'status' => 'active',
        ]);
    }

    public function test_flash_message_muncul_setelah_tambah_posisi(): void
    {
        $this->actingAs($this->admin)
            ->post(route('departments.positions.store', $this->department), [
                'name' => 'Manager Finance',
                'code' => 'FIN-02',
                'description' => 'Memimpin fungsi keuangan.',
            ])
            ->assertRedirect(route('departments.show', $this->department))
            ->assertSessionHas('success', 'Posisi berhasil ditambahkan');

        $this->actingAs($this->admin)
            ->withSession(['success' => 'Posisi berhasil ditambahkan'])
            ->get(route('departments.show', $this->department))
            ->assertOk()
            ->assertSee('alert alert-success text-white mx-4 mt-2', false)
            ->assertSee('fa-check-circle', false)
            ->assertSee('Posisi berhasil ditambahkan');
    }

    public function test_flash_message_muncul_setelah_edit_posisi(): void
    {
        $this->actingAs($this->admin)
            ->put(route('departments.positions.update', [$this->department, $this->position]), [
                'name' => 'Manager Finance',
                'code' => 'FIN-99',
                'description' => 'Memimpin fungsi keuangan.',
                'edit_position_id' => $this->position->id,
            ])
            ->assertRedirect(route('departments.show', $this->department))
            ->assertSessionHas('success', 'Posisi berhasil diperbarui');

        $this->actingAs($this->admin)
            ->withSession(['success' => 'Posisi berhasil diperbarui'])
            ->get(route('departments.show', $this->department))
            ->assertOk()
            ->assertSee('alert alert-success text-white mx-4 mt-2', false)
            ->assertSee('fa-check-circle', false)
            ->assertSee('Posisi berhasil diperbarui');
    }

    public function test_flash_message_muncul_setelah_hapus_posisi(): void
    {
        $this->actingAs($this->admin)
            ->delete(route('departments.positions.destroy', [$this->department, $this->position]))
            ->assertRedirect(route('departments.show', $this->department))
            ->assertSessionHas('success', 'Posisi berhasil dihapus');

        $this->actingAs($this->admin)
            ->withSession(['success' => 'Posisi berhasil dihapus'])
            ->get(route('departments.show', $this->department))
            ->assertOk()
            ->assertSee('alert alert-success text-white mx-4 mt-2', false)
            ->assertSee('fa-check-circle', false)
            ->assertSee('Posisi berhasil dihapus');
    }

    public function test_flash_message_error_muncul_jika_validasi_gagal(): void
    {
        $this->actingAs($this->admin)
            ->post(route('departments.positions.store', $this->department), [
                'name' => '',
            ])
            ->assertRedirect(route('departments.show', $this->department))
            ->assertSessionHasErrorsIn('addPosition', ['name'])
            ->assertSessionHas('error', 'Terjadi kesalahan, silakan coba lagi');

        $this->actingAs($this->admin)
            ->withSession(['error' => 'Terjadi kesalahan, silakan coba lagi'])
            ->get(route('departments.show', $this->department))
            ->assertOk()
            ->assertSee('alert alert-danger text-white mx-4 mt-2', false)
            ->assertSee('fa-exclamation-circle', false)
            ->assertSee('Terjadi kesalahan, silakan coba lagi');
    }
}
