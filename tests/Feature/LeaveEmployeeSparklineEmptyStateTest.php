<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveEmployeeSparklineEmptyStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_detail_shows_empty_state_when_all_months_are_zero(): void
    {
        $tenant = Tenant::create([
            'name' => 'Leave Sparkline Empty Tenant',
            'slug' => 'leave-sparkline-empty-tenant',
            'domain' => 'leave-sparkline-empty-tenant.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Leave Sparkline Empty Admin',
            'email' => 'leave-sparkline-empty-admin@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'LVE-SPK-EMP-0',
            'name' => 'Leave Sparkline Empty Employee',
            'email' => 'leave-sparkline-empty-employee@example.test',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('employees.leaves.show', [
            'employee' => $employee,
            'year' => 2026,
        ]));

        $response->assertOk();
        $response->assertSee('Sparkline Empty State');
        $response->assertSee('No leave days recorded for this year.');

        $sparkline = $response->viewData('employeeMonthlySparkline');

        $this->assertFalse($sparkline['has_data']);
        $this->assertSame([0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0], $sparkline['days']);
    }
}