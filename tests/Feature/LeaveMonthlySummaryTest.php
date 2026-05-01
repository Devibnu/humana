<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class LeaveMonthlySummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_monthly_summary_aggregates_requests_and_days_per_status(): void
    {
        $tenant = $this->makeTenant('leave-monthly-summary-tenant');
        $admin = $this->makeUser('admin_hr', $tenant, 'leave-monthly-summary-admin@example.test', 'Leave Monthly Summary Admin');
        $employee = $this->makeEmployee($tenant, 'LVE-MON-1', 'Leave Monthly Summary Employee', 'leave-monthly-summary-employee@example.test');

        $this->makeLeave($tenant, $employee, '2026-01-10', '2026-01-12', 'pending');
        $this->makeLeave($tenant, $employee, '2026-01-20', '2026-01-21', 'pending');
        $this->makeLeave($tenant, $employee, '2026-02-05', '2026-02-05', 'approved');
        $this->makeLeave($tenant, $employee, '2026-02-07', '2026-02-09', 'rejected');

        $response = $this->actingAs($admin)->get(route('leaves.index'));

        $response->assertOk();

        $monthlySummary = collect($response->viewData('monthlySummary'));

        $this->assertSummaryRow($monthlySummary, '2026-01', 'pending', 2, 5);
        $this->assertSummaryRow($monthlySummary, '2026-01', 'approved', 0, 0);
        $this->assertSummaryRow($monthlySummary, '2026-02', 'approved', 1, 1);
        $this->assertSummaryRow($monthlySummary, '2026-02', 'rejected', 1, 3);

        $chart = $response->viewData('monthlyTrendChart');

        $this->assertSame(['Jan 2026', 'Feb 2026'], $chart['labels']);
        $this->assertSame([2, 0], $chart['pending_requests']);
        $this->assertSame([0, 1], $chart['approved_requests']);
        $this->assertSame([0, 1], $chart['rejected_requests']);
        $this->assertSame([5, 4], $chart['total_days']);
    }

    protected function assertSummaryRow(Collection $rows, string $periodKey, string $status, int $requests, int $days): void
    {
        $row = $rows->first(fn (array $row) => $row['period_key'] === $periodKey && $row['status'] === $status);

        $this->assertNotNull($row);
        $this->assertSame($requests, $row['requests']);
        $this->assertSame($days, $row['days']);
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

    protected function makeEmployee(Tenant $tenant, string $code, string $name, string $email, ?User $user = null): Employee
    {
        return Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user?->id,
            'employee_code' => $code,
            'name' => $name,
            'email' => $email,
            'status' => 'active',
        ]);
    }

    protected function makeLeave(Tenant $tenant, Employee $employee, string $startDate, string $endDate, string $status): Leave
    {
        return Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => 'Monthly '.$status,
            'status' => $status,
        ]);
    }
}