<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\FamilyMember;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyMembersTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $admin;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name'   => 'Family Test Tenant',
            'slug'   => 'family-test',
            'domain' => 'family-test.test',
            'status' => 'active',
        ]);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Family Admin',
            'email'     => 'family-admin@example.test',
            'password'  => 'password',
            'role'      => 'admin_hr',
            'status'    => 'active',
        ]);

        $this->employee = Employee::create([
            'tenant_id'     => $this->tenant->id,
            'employee_code' => 'EMP-FAM-001',
            'name'          => 'Test Employee Family',
            'email'         => 'test-emp-family@example.test',
            'status'        => 'active',
        ]);
    }

    public function test_employee_show_page_renders_with_family_tab(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('employees.show', $this->employee));

        $response->assertOk();
        $response->assertSee('Data Keluarga');
        $response->assertSee('data-testid="tab-family"', false);
        $response->assertSee('data-testid="family-empty-state"', false);
    }

    public function test_admin_can_add_family_member(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('employees.family-members.store', $this->employee), [
                'name'           => 'Siti Rahayu',
                'relationship'   => 'pasangan',
                'dob'            => '1992-03-15',
                'education'      => 'S1',
                'job'            => 'Guru',
                'marital_status' => 'menikah',
            ]);

        $response->assertRedirect(route('employees.show', $this->employee));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('family_members', [
            'employee_id'    => $this->employee->id,
            'name'           => 'Siti Rahayu',
            'relationship'   => 'pasangan',
            'education'      => 'S1',
            'job'            => 'Guru',
            'marital_status' => 'menikah',
        ]);
    }

    public function test_family_member_appears_in_employee_show(): void
    {
        FamilyMember::create([
            'employee_id'    => $this->employee->id,
            'name'           => 'Budi Santoso',
            'relationship'   => 'anak',
            'dob'            => '2014-06-10',
            'marital_status' => 'belum_menikah',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('employees.show', $this->employee));

        $response->assertOk();
        $response->assertSee('Budi Santoso');
        $response->assertSee('Anak');
        $response->assertSee('data-testid="family-table"', false);
    }

    public function test_admin_can_update_family_member(): void
    {
        $member = FamilyMember::create([
            'employee_id'    => $this->employee->id,
            'name'           => 'Ayu Lestari',
            'relationship'   => 'anak',
            'dob'            => '2011-04-20',
            'marital_status' => 'belum_menikah',
        ]);

        $response = $this->actingAs($this->admin)
            ->put(route('employees.family-members.update', [$this->employee, $member]), [
                'name'           => 'Ayu Lestari Updated',
                'relationship'   => 'anak',
                'dob'            => '2011-04-20',
                'education'      => 'SMA',
                'job'            => 'Pelajar',
                'marital_status' => 'belum_menikah',
            ]);

        $response->assertRedirect(route('employees.show', $this->employee));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('family_members', [
            'id'        => $member->id,
            'name'      => 'Ayu Lestari Updated',
            'education' => 'SMA',
        ]);
    }

    public function test_admin_can_delete_family_member(): void
    {
        $member = FamilyMember::create([
            'employee_id'    => $this->employee->id,
            'name'           => 'To Be Deleted',
            'relationship'   => 'saudara',
            'dob'            => '1998-12-01',
            'marital_status' => 'cerai',
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('employees.family-members.destroy', [$this->employee, $member]));

        $response->assertRedirect(route('employees.show', $this->employee));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('family_members', ['id' => $member->id]);
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs($this->admin)
            ->post(route('employees.family-members.store', $this->employee), [])
            ->assertSessionHasErrors(['name', 'relationship', 'dob', 'marital_status']);
    }

    public function test_store_rejects_invalid_relationship(): void
    {
        $this->actingAs($this->admin)
            ->post(route('employees.family-members.store', $this->employee), [
                'name'           => 'Test',
                'relationship'   => 'tetangga',
                'dob'            => '2012-10-10',
                'marital_status' => 'belum_menikah',
            ])
            ->assertSessionHasErrors('relationship');
    }

    public function test_store_accepts_free_text_education(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('employees.family-members.store', $this->employee), [
                'name'           => 'Test',
                'relationship'   => 'anak',
                'dob'            => '2015-02-15',
                'education'      => 'SARJANA',
                'marital_status' => 'belum_menikah',
            ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('family_members', [
            'employee_id' => $this->employee->id,
            'name'        => 'Test',
            'education'   => 'SARJANA',
        ]);
    }

    public function test_employee_role_cannot_add_family_member(): void
    {
        $employeeUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Emp User',
            'email'     => 'emp-user-fam@example.test',
            'password'  => 'password',
            'role'      => 'employee',
            'status'    => 'active',
        ]);

        $this->actingAs($employeeUser)
            ->post(route('employees.family-members.store', $this->employee), [
                'name'           => 'Should Fail',
                'relationship'   => 'anak',
                'dob'            => '2016-02-11',
                'marital_status' => 'belum_menikah',
            ])
            ->assertForbidden();
    }

    public function test_cannot_delete_family_member_belonging_to_another_employee(): void
    {
        $otherEmployee = Employee::create([
            'tenant_id'     => $this->tenant->id,
            'employee_code' => 'EMP-OTHER-FAM',
            'name'          => 'Other Employee',
            'email'         => 'other-emp-fam@example.test',
            'status'        => 'active',
        ]);

        $member = FamilyMember::create([
            'employee_id'    => $otherEmployee->id,
            'name'           => 'Not Mine',
            'relationship'   => 'anak',
            'dob'            => '2016-08-11',
            'marital_status' => 'belum_menikah',
        ]);

        $this->actingAs($this->admin)
            ->delete(route('employees.family-members.destroy', [$this->employee, $member]))
            ->assertForbidden();
    }

    public function test_admin_can_add_family_member_with_marital_status(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('employees.family-members.store', $this->employee), [
                'name'           => 'Ahmad Fauzi',
                'relationship'   => 'pasangan',
                'dob'            => '1990-01-02',
                'marital_status' => 'menikah',
            ]);

        $response->assertRedirect(route('employees.show', $this->employee));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('family_members', [
            'employee_id'    => $this->employee->id,
            'name'           => 'Ahmad Fauzi',
            'marital_status' => 'menikah',
        ]);
    }

    public function test_store_rejects_invalid_marital_status(): void
    {
        $this->actingAs($this->admin)
            ->post(route('employees.family-members.store', $this->employee), [
                'name'           => 'Test',
                'relationship'   => 'anak',
                'dob'            => '2012-09-10',
                'marital_status' => 'janda_baru',
            ])
            ->assertSessionHasErrors('marital_status');
    }

    public function test_marital_status_label_renders_in_family_table(): void
    {
        FamilyMember::create([
            'employee_id'    => $this->employee->id,
            'name'           => 'Dewi Sartika',
            'relationship'   => 'pasangan',
            'dob'            => '1993-07-07',
            'marital_status' => 'menikah',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('employees.show', $this->employee));

        $response->assertOk();
        $response->assertSee('Menikah');
        $response->assertSee('Status Nikah');
    }
}
