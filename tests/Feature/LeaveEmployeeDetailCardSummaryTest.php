<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class LeaveEmployeeDetailCardSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_detail_cards_follow_selected_month_year_filter(): void
    {
        $tenant = Tenant::create([
            'name' => 'Leave Employee Card Summary Tenant',
            'slug' => 'leave-employee-card-summary-tenant',
            'domain' => 'leave-employee-card-summary-tenant.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Leave Employee Card Summary Admin',
            'email' => 'leave-employee-card-summary-admin@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'LVE-EMP-CARD-1',
            'name' => 'Leave Employee Card Summary Employee',
            'email' => 'leave-employee-card-summary-employee@example.test',
            'status' => 'active',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-02',
            'reason' => 'Card pending included',
            'status' => 'pending',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'permission',
            'start_date' => '2026-04-10',
            'end_date' => '2026-04-12',
            'reason' => 'Card approved included',
            'status' => 'approved',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'sick',
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-01',
            'reason' => 'Card excluded',
            'status' => 'rejected',
        ]);

        $response = $this->actingAs($admin)->get(route('employees.leaves.show', [
            'employee' => $employee,
            'month' => 4,
            'year' => 2026,
        ]));

        $response->assertOk();
        $response->assertSee('April 2026 Summary');

        $cards = collect($response->viewData('employeeCardSummary'));

        $this->assertCard($cards, 'April 2026', 'pending', 1, 2);
        $this->assertCard($cards, 'April 2026', 'approved', 1, 3);
        $this->assertCard($cards, 'April 2026', 'rejected', 0, 0);
    }

    protected function assertCard(Collection $cards, string $scope, string $status, int $requests, int $days): void
    {
        $card = $cards->first(fn (array $card) => $card['filter_scope'] === $scope && $card['status'] === $status);

        $this->assertNotNull($card);
        $this->assertSame($requests, $card['requests']);
        $this->assertSame($days, $card['days']);
    }
}