<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveAnalyticsLeaveTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_analytics_chart_shows_leave_type_name(): void
    {
        $tenant = Tenant::create([
            'name' => 'JS',
            'slug' => 'js-analytics',
            'domain' => 'js-analytics.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin HR',
            'email' => 'admin@humana.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $leaveType = LeaveType::create([
            'tenant_id' => $tenant->id,
            'name' => 'Cuti Tahunan',
            'is_paid' => true,
        ]);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'LAT-001',
            'name' => 'Budi',
            'email' => 'budi@humana.test',
            'status' => 'active',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-02',
            'reason' => 'Cuti tahunan',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($admin)->get(route('leaves.analytics', [
            'tenant_id' => $tenant->id,
            'year' => 2026,
            'month' => 5,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Cuti Tahunan');
    }
}
