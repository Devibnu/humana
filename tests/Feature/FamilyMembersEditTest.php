<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\FamilyMember;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyMembersEditTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $admin;
    private Employee $employee;
    private FamilyMember $familyMember;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name'   => 'Edit Family Tenant',
            'slug'   => 'edit-family-tenant',
            'domain' => 'edit-family.test',
            'status' => 'active',
        ]);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Edit Family Admin',
            'email'     => 'edit-family-admin@example.test',
            'password'  => 'password',
            'role'      => 'admin_hr',
            'status'    => 'active',
        ]);

        $this->employee = Employee::create([
            'tenant_id'     => $this->tenant->id,
            'employee_code' => 'EMP-FAMILY-EDIT-001',
            'name'          => 'Edit Family Employee',
            'email'         => 'edit-family-employee@example.test',
            'status'        => 'active',
        ]);

        $this->familyMember = FamilyMember::create([
            'employee_id'    => $this->employee->id,
            'name'           => 'Rina Awal',
            'relationship'   => 'pasangan',
            'dob'            => '1991-03-04',
            'education'      => 'SMA',
            'job'            => 'Ibu Rumah Tangga',
            'marital_status' => 'menikah',
        ]);
    }

    public function test_modal_edit_muncul_di_halaman_detail(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('employees.show', $this->employee));

        $response->assertOk();
        $response->assertSee('id="editFamilyModal'.$this->familyMember->id.'"', false);
        $response->assertSee('Edit Anggota Keluarga');
        $response->assertSee(route('family-members.update', [$this->employee, $this->familyMember]), false);
        $response->assertSee('Hubungan keluarga sesuai KK');
        $response->assertSee('Format: dd/mm/yyyy');
        $response->assertSee('Pilih sesuai dokumen resmi');
    }

    public function test_field_edit_terisi_sesuai_data(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('employees.show', $this->employee));

        $response->assertOk();
        $response->assertSee('value="Rina Awal"', false);
        $response->assertSee('value="1991-03-04"', false);
        $response->assertSee('value="SMA"', false);
        $response->assertSee('value="Ibu Rumah Tangga"', false);
        $response->assertSee('selected', false);
    }

    public function test_perubahan_data_tersimpan(): void
    {
        $response = $this->actingAs($this->admin)
            ->put(route('family-members.update', [$this->employee, $this->familyMember]), [
                'name'           => 'Rina Update',
                'relationship'   => 'orang_tua',
                'dob'            => '1988-08-09',
                'education'      => 'S1',
                'job'            => 'Wiraswasta',
                'marital_status' => 'cerai',
            ]);

        $response->assertRedirect(route('employees.show', $this->employee));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('family_members', [
            'id'             => $this->familyMember->id,
            'name'           => 'Rina Update',
            'relationship'   => 'orang_tua',
            'dob'            => '1988-08-09',
            'education'      => 'S1',
            'job'            => 'Wiraswasta',
            'marital_status' => 'cerai',
        ]);
    }
}