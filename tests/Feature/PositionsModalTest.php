<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PositionsModalTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $admin;
    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Tenant Modal Posisi',
            'code' => 'TEN-POS-MODAL',
            'slug' => 'tenant-modal-posisi',
            'domain' => 'tenant-modal-posisi.test',
            'status' => 'active',
        ]);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin Modal Posisi',
            'email' => 'admin-modal-posisi@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $this->department = Department::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Keuangan',
            'code' => 'FIN',
            'description' => 'Mengelola administrasi dan pelaporan keuangan.',
            'status' => 'active',
        ]);
    }

    public function test_detail_departemen_tidak_menampilkan_tab_posisi(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('departments.show', $this->department));

        $response->assertOk();
        $response->assertDontSee('data-testid="tab-positions"', false);
        $response->assertDontSee('data-testid="btn-open-add-position-modal"', false);
        $response->assertDontSee('id="addPositionModal"', false);
        $response->assertDontSee('Tambah Posisi');
        $response->assertDontSee(route('departments.positions.store', $this->department), false);
    }

    public function test_field_wajib_tidak_boleh_kosong(): void
    {
        $this->actingAs($this->admin)
            ->post(route('departments.positions.store', $this->department), [])
            ->assertSessionHasErrorsIn('addPosition', ['name']);
    }

    public function test_posisi_baru_tersimpan_dengan_relasi_department_id(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('departments.positions.store', $this->department), [
                'name' => 'Manajer Keuangan',
                'code' => 'MGR-01',
                'description' => 'Memimpin tim keuangan dan proses budgeting.',
            ]);

        $response->assertRedirect(route('departments.show', $this->department));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('positions', [
            'tenant_id' => $this->tenant->id,
            'department_id' => $this->department->id,
            'name' => 'Manajer Keuangan',
            'code' => 'MGR-01',
            'description' => 'Memimpin tim keuangan dan proses budgeting.',
            'status' => 'active',
        ]);
    }
}
