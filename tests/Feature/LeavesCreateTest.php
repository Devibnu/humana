<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeavesCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_form_tampil_dengan_field_lengkap(): void
    {
        [$admin, $tenant, $employee] = $this->makeContext();

        $response = $this->actingAs($admin)->get(route('leaves.create'));

        $response->assertOk();
        $response->assertSee('Tambah Permintaan Cuti');
        $response->assertSee('data-testid="leaves-create-form"', false);
        $response->assertSee('name="tenant_id"', false);
        $response->assertSee('name="employee_id"', false);
        $response->assertSee('name="leave_type_id"', false);
        $response->assertSee('name="start_date"', false);
        $response->assertSee('name="end_date"', false);
        $response->assertSee('name="reason"', false);
        $response->assertSee('Tuliskan alasan cuti');
        $response->assertSee('Manager dapat meninjau permintaan cuti tenant scoped');
        $response->assertSee($tenant->name);
        $response->assertSee($employee->name);
    }

    public function test_validasi_jalan(): void
    {
        [$admin] = $this->makeContext();

        $response = $this->actingAs($admin)->from(route('leaves.create'))->post(route('leaves.store'), []);

        $response->assertRedirect(route('leaves.create'));
        $response->assertSessionHasErrors([
            'tenant_id',
            'employee_id',
            'leave_type_id',
            'start_date',
            'end_date',
            'reason',
        ]);
    }

    public function test_leave_request_tersimpan_dengan_relasi_employee_dan_tenant(): void
    {
        [$admin, $tenant, $employee] = $this->makeContext();
        $leaveTypeId = LeaveType::where('tenant_id', $tenant->id)->value('id');

        $response = $this->actingAs($admin)->post(route('leaves.store'), [
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveTypeId,
            'start_date' => '2026-04-25',
            'end_date' => '2026-04-27',
            'reason' => 'Libur keluarga',
            'status' => 'approved',
        ]);

        $response->assertRedirect(route('leaves.index'));
        $response->assertSessionHas('success', 'Permintaan cuti berhasil ditambahkan');

        $this->assertDatabaseHas('leaves', [
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'reason' => 'Libur keluarga',
            'status' => 'pending',
            'leave_type_id' => $leaveTypeId,
        ]);

        $leave = Leave::query()->latest('id')->first();

        $this->assertNotNull($leave);
        $this->assertSame($tenant->id, $leave->tenant_id);
        $this->assertSame($employee->id, $leave->employee_id);
    }

    protected function makeContext(): array
    {
        $tenant = Tenant::create([
            'name' => 'Leaves Create Tenant',
            'slug' => 'leaves-create-tenant',
            'domain' => 'leaves-create-tenant.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Leaves Create Admin',
            'email' => 'leaves-create-admin@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'LVC-001',
            'name' => 'Leaves Create Employee',
            'email' => 'leaves-create-employee@example.test',
            'status' => 'active',
        ]);

        LeaveType::create([
            'tenant_id' => $tenant->id,
            'name' => 'Cuti Tahunan',
            'is_paid' => true,
        ]);

        return [$admin, $tenant, $employee];
    }
}