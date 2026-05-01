<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Position;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PositionsEditModalTest extends TestCase
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
            'name' => 'Tenant Edit Posisi',
            'code' => 'TEN-POS-EDIT',
            'slug' => 'tenant-edit-posisi',
            'domain' => 'tenant-edit-posisi.test',
            'status' => 'active',
        ]);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin Edit Posisi',
            'email' => 'admin-edit-posisi@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $this->department = Department::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Operasional',
            'code' => 'OPS',
            'description' => 'Mengatur proses operasional harian.',
            'status' => 'active',
        ]);

        $this->position = Position::create([
            'tenant_id' => $this->tenant->id,
            'department_id' => $this->department->id,
            'name' => 'Supervisor Operasional',
            'code' => 'OPS-01',
            'description' => 'Mengawasi pelaksanaan operasional.',
            'status' => 'active',
        ]);
    }

    public function test_detail_departemen_tidak_menampilkan_modal_edit_posisi(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('departments.show', $this->department));

        $response->assertOk();
        $response->assertDontSee('data-testid="btn-edit-position-'.$this->position->id.'"', false);
        $response->assertDontSee('data-testid="position-edit-modal-'.$this->position->id.'"', false);
        $response->assertDontSee('id="editPositionModal'.$this->position->id.'"', false);
        $response->assertDontSee(route('departments.positions.update', [$this->department, $this->position]), false);
    }

    public function test_halaman_edit_posisi_memuat_data_yang_akan_diedit(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('positions.edit', $this->position));

        $response->assertOk();
        $response->assertSee('Edit Posisi');
        $response->assertSee('data-testid="positions-edit-card"', false);
        $response->assertSee('data-testid="positions-edit-form"', false);
        $response->assertSee('Perbarui tenant, departemen, kode internal, dan status operasional posisi.');
        $response->assertSee('value="Supervisor Operasional"', false);
        $response->assertSee('value="OPS-01"', false);
        $response->assertSee('Mengawasi pelaksanaan operasional.');
        $response->assertSee('Simpan Perubahan');
    }

    public function test_perubahan_tersimpan_dengan_relasi_department_id(): void
    {
        $response = $this->actingAs($this->admin)
            ->put(route('departments.positions.update', [$this->department, $this->position]), [
                'name' => 'Manager Operasional',
                'code' => 'OPS-99',
                'description' => 'Memimpin strategi operasional harian.',
                'edit_position_id' => $this->position->id,
            ]);

        $response->assertRedirect(route('departments.show', $this->department));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('positions', [
            'id' => $this->position->id,
            'tenant_id' => $this->tenant->id,
            'department_id' => $this->department->id,
            'name' => 'Manager Operasional',
            'code' => 'OPS-99',
            'description' => 'Memimpin strategi operasional harian.',
        ]);
    }
}
