<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Position;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PositionsDeleteModalTest extends TestCase
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
            'name' => 'Tenant Delete Posisi',
            'code' => 'TEN-POS-DEL',
            'slug' => 'tenant-delete-posisi',
            'domain' => 'tenant-delete-posisi.test',
            'status' => 'active',
        ]);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin Delete Posisi',
            'email' => 'admin-delete-posisi@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $this->department = Department::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Accounting',
            'code' => 'ACC',
            'description' => 'Mengelola pencatatan akuntansi tenant.',
            'status' => 'active',
        ]);

        $this->position = Position::create([
            'tenant_id' => $this->tenant->id,
            'department_id' => $this->department->id,
            'name' => 'Akuntan Senior',
            'code' => 'ACC-01',
            'description' => 'Mengelola jurnal dan pelaporan.',
            'status' => 'active',
        ]);
    }

    public function test_detail_departemen_tidak_menampilkan_modal_hapus_posisi(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('departments.show', $this->department));

        $response->assertOk();
        $response->assertDontSee('data-testid="btn-delete-position-'.$this->position->id.'"', false);
        $response->assertDontSee('data-testid="position-delete-modal-'.$this->position->id.'"', false);
        $response->assertDontSee('id="deletePositionModal-'.$this->position->id.'"', false);
        $response->assertDontSee('Konfirmasi Hapus Posisi');
        $response->assertDontSee('Apakah Anda yakin ingin menghapus posisi');
        $response->assertDontSee(route('departments.positions.destroy', [$this->department->id, $this->position->id]), false);
    }

    public function test_posisi_terhapus_setelah_konfirmasi(): void
    {
        $response = $this->actingAs($this->admin)
            ->delete(route('departments.positions.destroy', [$this->department, $this->position]));

        $response->assertRedirect(route('departments.show', $this->department));
        $response->assertSessionHas('success', 'Posisi berhasil dihapus');

        $this->assertDatabaseMissing('positions', [
            'id' => $this->position->id,
        ]);
    }

    public function test_posisi_tetap_ada_jika_dibatalkan(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('departments.show', $this->department));

        $response->assertOk();

        $this->assertDatabaseHas('positions', [
            'id' => $this->position->id,
            'department_id' => $this->department->id,
            'name' => 'Akuntan Senior',
        ]);
    }

    public function test_flash_message_sesuai_aksi_hapus(): void
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
}
