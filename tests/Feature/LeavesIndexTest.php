<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeavesIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_tabel_tampil_dengan_data_leave(): void
    {
        [$admin, $tenant, $employee] = $this->makeContext();

        $leave = Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => '2026-04-20',
            'end_date' => '2026-04-22',
            'reason' => 'Pulang kampung keluarga',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($admin)->get(route('leaves.index'));

        $response->assertOk();
        $response->assertSee('Daftar Permintaan Cuti');
        $response->assertSee('data-testid="leaves-table"', false);
        $response->assertSee('Leaves Index Employee');
        $response->assertSee('Cuti Tahunan');
        $response->assertSee('20 Apr 2026');
        $response->assertSee('22 Apr 2026');
        $response->assertSee('3 hari');
        $response->assertSee('Pulang kampung keluarga');
        $response->assertSee('data-testid="leave-status-'.$leave->id.'"', false);
        $response->assertSee('Approved');
        $response->assertSee('data-testid="btn-open-add-leave-modal"', false);
        $response->assertSee('data-testid="btn-view-leave-'.$leave->id.'"', false);
        $response->assertSee('data-testid="btn-edit-leave-'.$leave->id.'"', false);
        $response->assertSee('data-testid="btn-delete-leave-'.$leave->id.'"', false);
        $response->assertSee('title="Lihat"', false);
        $response->assertSee('title="Edit"', false);
        $response->assertSee('title="Hapus"', false);
    }

    public function test_filter_tanggal_bekerja(): void
    {
        [$admin, $tenant, $employee] = $this->makeContext();

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => '2026-04-20',
            'end_date' => '2026-04-21',
            'reason' => 'Leave in range',
            'status' => 'pending',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'sick',
            'start_date' => '2026-04-10',
            'end_date' => '2026-04-10',
            'reason' => 'Leave out of range',
            'status' => 'rejected',
        ]);

        $response = $this->actingAs($admin)->get(route('leaves.index', [
            'start_date' => '2026-04-19',
            'end_date' => '2026-04-21',
        ]));

        $response->assertOk();
        $response->assertSee('2026-04-19');
        $response->assertSee('2026-04-21');
        $response->assertSee('20 Apr 2026');
        $response->assertDontSee('10 Apr 2026');
    }

    public function test_badge_summary_sesuai_data(): void
    {
        [$admin, $tenant, $employee] = $this->makeContext();

        $rows = [
            ['status' => 'pending', 'start_date' => '2026-04-18', 'end_date' => '2026-04-19'],
            ['status' => 'approved', 'start_date' => '2026-04-20', 'end_date' => '2026-04-22'],
            ['status' => 'rejected', 'start_date' => '2026-04-23', 'end_date' => '2026-04-23'],
        ];

        foreach ($rows as $index => $row) {
            Leave::create([
                'tenant_id' => $tenant->id,
                'employee_id' => $employee->id,
                'leave_type' => 'annual',
                'start_date' => $row['start_date'],
                'end_date' => $row['end_date'],
                'reason' => 'Leave '.$index,
                'status' => $row['status'],
            ]);
        }

        $response = $this->actingAs($admin)->get(route('leaves.index'));

        $response->assertOk();
        $response->assertSee('data-testid="leaves-summary-pending"', false);
        $response->assertSee('data-testid="leaves-summary-approved"', false);
        $response->assertSee('data-testid="leaves-summary-rejected"', false);
        $response->assertSee('Pending: 1 permintaan / 2 hari');
        $response->assertSee('Approved: 1 permintaan / 3 hari');
        $response->assertSee('Rejected: 1 permintaan / 1 hari');
    }

    public function test_empty_state_muncul_jika_data_kosong(): void
    {
        [$admin] = $this->makeContext();

        $response = $this->actingAs($admin)->get(route('leaves.index'));

        $response->assertOk();
        $response->assertSee('data-testid="leaves-empty-state"', false);
        $response->assertSee('Belum ada permintaan cuti untuk periode ini');
    }

    protected function makeContext(): array
    {
        $tenant = Tenant::create([
            'name' => 'Leaves Index Tenant',
            'slug' => 'leaves-index-tenant',
            'domain' => 'leaves-index-tenant.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Leaves Index Admin',
            'email' => 'leaves-index-admin@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'LVI-001',
            'name' => 'Leaves Index Employee',
            'email' => 'leaves-index-employee@example.test',
            'status' => 'active',
        ]);

        return [$admin, $tenant, $employee];
    }
}