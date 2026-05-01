<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveSummaryDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_badges_show_request_count_and_total_days(): void
    {
        $tenant = Tenant::create([
            'name' => 'Leave Dashboard Summary Tenant',
            'slug' => 'leave-dashboard-summary-tenant',
            'domain' => 'leave-dashboard-summary-tenant.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Leave Dashboard Admin',
            'email' => 'leave-dashboard-admin@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'LVE-DSH-1',
            'name' => 'Leave Dashboard Employee',
            'email' => 'leave-dashboard-employee@example.test',
            'status' => 'active',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => '2026-04-10',
            'end_date' => '2026-04-12',
            'reason' => 'Pending leave one',
            'status' => 'pending',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => '2026-04-14',
            'end_date' => '2026-04-15',
            'reason' => 'Pending leave two',
            'status' => 'pending',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'sick',
            'start_date' => '2026-04-16',
            'end_date' => '2026-04-16',
            'reason' => 'Approved leave',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($admin)->get(route('leaves.index'));

        $response->assertOk();
    $response->assertSee('Pending: 2 permintaan / 5 hari');
    $response->assertSee('Approved: 1 permintaan / 1 hari');
    $response->assertSee('Rejected: 0 permintaan / 0 hari');
    }
}