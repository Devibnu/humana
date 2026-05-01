<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class LeaveDashboardCardSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_cards_show_selected_month_year_summary(): void
    {
        $tenant = $this->makeTenant('leave-dashboard-card-tenant');
        $admin = $this->makeUser('admin_hr', $tenant, 'leave-dashboard-card-admin@example.test', 'Leave Dashboard Card Admin');
        $employee = $this->makeEmployee($tenant, 'LVE-CARD-1', 'Leave Dashboard Card Employee', 'leave-dashboard-card-employee@example.test');

        $this->makeLeave($tenant, $employee, '2026-04-02', '2026-04-03', 'pending', 'Card Pending April');
        $this->makeLeave($tenant, $employee, '2026-04-10', '2026-04-12', 'approved', 'Card Approved April');
        $this->makeLeave($tenant, $employee, '2026-04-20', '2026-04-20', 'rejected', 'Card Rejected April');
        $this->makeLeave($tenant, $employee, '2026-05-01', '2026-05-02', 'pending', 'Card Pending May');

        $response = $this->actingAs($admin)->get(route('leaves.index', [
            'month' => 4,
            'year' => 2026,
        ]));

        $response->assertOk();
        $response->assertSee('Daftar Permintaan Cuti');

        $cards = collect($response->viewData('dashboardCardSummary'));

        $this->assertCard($cards, 'April 2026', 'pending', 1, 2);
        $this->assertCard($cards, 'April 2026', 'approved', 1, 3);
        $this->assertCard($cards, 'April 2026', 'rejected', 1, 1);
    }

    protected function assertCard(Collection $cards, string $scope, string $status, int $requests, int $days): void
    {
        $card = $cards->first(fn (array $card) => $card['filter_scope'] === $scope && $card['status'] === $status);

        $this->assertNotNull($card);
        $this->assertSame($requests, $card['requests']);
        $this->assertSame($days, $card['days']);
    }

    protected function makeTenant(string $slug): Tenant
    {
        return Tenant::create([
            'name' => ucwords(str_replace('-', ' ', $slug)),
            'slug' => $slug,
            'domain' => $slug.'.test',
            'status' => 'active',
        ]);
    }

    protected function makeUser(string $role, Tenant $tenant, string $email, string $name): User
    {
        return User::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'email' => $email,
            'password' => 'password',
            'role' => $role,
            'status' => 'active',
        ]);
    }

    protected function makeEmployee(Tenant $tenant, string $code, string $name, string $email): Employee
    {
        return Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => $code,
            'name' => $name,
            'email' => $email,
            'status' => 'active',
        ]);
    }

    protected function makeLeave(Tenant $tenant, Employee $employee, string $startDate, string $endDate, string $status, string $reason): Leave
    {
        return Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => $reason,
            'status' => $status,
        ]);
    }
}