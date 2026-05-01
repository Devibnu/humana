<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyMembersModalTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $admin;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name'   => 'Modal Family Tenant',
            'slug'   => 'modal-family-tenant',
            'domain' => 'modal-family.test',
            'status' => 'active',
        ]);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Modal Family Admin',
            'email'     => 'modal-family-admin@example.test',
            'password'  => 'password',
            'role'      => 'admin_hr',
            'status'    => 'active',
        ]);

        $this->employee = Employee::create([
            'tenant_id'     => $this->tenant->id,
            'employee_code' => 'EMP-FAMILY-MODAL-001',
            'name'          => 'Modal Family Employee',
            'email'         => 'modal-family-employee@example.test',
            'status'        => 'active',
        ]);
    }

    public function test_modal_muncul_di_halaman_detail_karyawan(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('employees.show', $this->employee));

        $response->assertOk();
        $response->assertSee('id="addFamilyMemberModal"', false);
        $response->assertSee('Tambah Anggota Keluarga');
        $response->assertSee(route('family-members.store', $this->employee), false);
    }

    public function test_field_wajib_tidak_boleh_kosong(): void
    {
        $this->actingAs($this->admin)
            ->post(route('family-members.store', $this->employee), [])
            ->assertSessionHasErrors(['name', 'relationship', 'dob', 'marital_status']);
    }

    public function test_data_tersimpan_ke_tabel_family_members(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('family-members.store', $this->employee), [
                'name'           => 'Rina Hartati',
                'relationship'   => 'pasangan',
                'dob'            => '1994-02-12',
                'education'      => 'S1',
                'job'            => 'Wiraswasta',
                'marital_status' => 'menikah',
            ]);

        $response->assertRedirect(route('employees.show', $this->employee));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('family_members', [
            'employee_id'    => $this->employee->id,
            'name'           => 'Rina Hartati',
            'relationship'   => 'pasangan',
            'dob'            => '1994-02-12',
            'education'      => 'S1',
            'job'            => 'Wiraswasta',
            'marital_status' => 'menikah',
        ]);
    }
}