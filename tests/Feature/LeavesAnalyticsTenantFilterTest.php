<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeavesAnalyticsTenantFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-04-21 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_dropdown_tenant_tampil_untuk_admin_multi_tenant(): void
    {
        [$admin, $tenantA, $tenantB] = $this->makeContext();

        $response = $this->actingAs($admin)->get(route('leaves.analytics'));

        $response->assertOk();
        $response->assertViewIs('leaves.analytics');
        $response->assertSee('data-testid="leave-analytics-tenant-filter"', false);
        $response->assertSee('Pilih tenant untuk melihat analitik cuti');
        $response->assertSee('Tenant: '.$tenantA->name);
        $response->assertSee($tenantA->name);
        $response->assertSee($tenantB->name);
        $this->assertTrue($response->viewData('canSwitchTenant'));
        $this->assertSame($tenantA->id, $response->viewData('tenant')->id);
    }

    public function test_badge_tenant_tampil_untuk_manager(): void
    {
        [, $tenantA, , $manager] = $this->makeContext();

        $managerResponse = $this->actingAs($manager)->get(route('leaves.analytics'));

        $managerResponse->assertOk();
        $managerResponse->assertSee('data-testid="leave-analytics-tenant-scope-badge"', false);
        $managerResponse->assertSee('Tenant: '.$tenantA->name);
        $managerResponse->assertDontSee('data-testid="leave-analytics-tenant-filter"', false);
    }

    public function test_employee_tidak_bisa_mengakses_dashboard_analytics(): void
    {
        [, , , , $employeeUser] = $this->makeContext();

        $employeeResponse = $this->actingAs($employeeUser)->get(route('leaves.analytics'));

        $employeeResponse->assertForbidden();
    }

    public function test_data_analytics_berubah_sesuai_tenant_yang_dipilih(): void
    {
        [$admin, , $tenantB] = $this->makeContext();

        $response = $this->actingAs($admin)->get(route('leaves.analytics', [
            'tenant_id' => $tenantB->id,
        ]));

        $response->assertOk();
        $response->assertSee('Tenant: '.$tenantB->name);

        $summary = $response->viewData('summary');
        $charts = $response->viewData('charts');

        $this->assertSame($tenantB->id, $response->viewData('tenant')->id);
        $this->assertSame(['requests' => 0, 'days' => 0, 'percentage' => 0.0], $summary['pending']);
        $this->assertSame(['requests' => 0, 'days' => 0, 'percentage' => 0.0], $summary['approved']);
        $this->assertSame(['requests' => 1, 'days' => 1, 'percentage' => 100.0], $summary['rejected']);
        $this->assertSame(['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'], $charts['monthlyTrend']['labels']);
        $this->assertSame([0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0], $charts['monthlyTrend']['pending_requests']);
        $this->assertSame([0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0], $charts['monthlyTrend']['approved_requests']);
        $this->assertSame([0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0], $charts['monthlyTrend']['rejected_requests']);
        $this->assertSame([0, 0, 1], $charts['statusDays']['days']);
    }

    public function test_manager_tidak_bisa_switch_tenant(): void
    {
        [, $tenantA, $tenantB, $manager] = $this->makeContext();

        $managerResponse = $this->actingAs($manager)->get(route('leaves.analytics', [
            'tenant_id' => $tenantB->id,
        ]));

        $managerResponse->assertOk();
        $managerResponse->assertSee('Tenant: '.$tenantA->name);
        $this->assertSame($tenantA->id, $managerResponse->viewData('tenant')->id);
        $this->assertSame(['requests' => 1, 'days' => 2, 'percentage' => 33.3], $managerResponse->viewData('summary')['pending']);
        $this->assertSame(['requests' => 1, 'days' => 3, 'percentage' => 33.3], $managerResponse->viewData('summary')['approved']);
        $this->assertSame(['requests' => 1, 'days' => 4], $managerResponse->viewData('annual')[4]['rejected']);
    }

    protected function makeContext(): array
    {
        $tenantA = $this->makeTenant('leaves-analytics-tenant-a');
        $tenantB = $this->makeTenant('leaves-analytics-tenant-b');
        $admin = $this->makeUser('admin_hr', $tenantA, 'leaves-analytics-admin@example.test', 'Leaves Analytics Admin');
        $manager = $this->makeUser('manager', $tenantA, 'leaves-analytics-manager@example.test', 'Leaves Analytics Manager');
        $employeeUser = $this->makeUser('employee', $tenantA, 'leaves-analytics-employee@example.test', 'Leaves Analytics Employee');
        $employeeA = $this->makeEmployee($tenantA, 'LVA-001', 'Leaves Analytics Employee A', 'leaves-analytics-employee-a@example.test', $employeeUser);
        $employeeAOther = $this->makeEmployee($tenantA, 'LVA-002', 'Leaves Analytics Employee A Other', 'leaves-analytics-employee-a-other@example.test');
        $employeeB = $this->makeEmployee($tenantB, 'LVB-001', 'Leaves Analytics Employee B', 'leaves-analytics-employee-b@example.test');

        $this->makeLeave($tenantA, $employeeA, '2026-04-01', '2026-04-02', 'pending', 'Alpha pending');
        $this->makeLeave($tenantA, $employeeA, '2026-04-10', '2026-04-12', 'approved', 'Alpha approved');
        $this->makeLeave($tenantA, $employeeAOther, '2026-04-15', '2026-04-18', 'rejected', 'Alpha rejected');
        $this->makeLeave($tenantB, $employeeB, '2026-02-05', '2026-02-07', 'approved', 'Beta approved');
        $this->makeLeave($tenantB, $employeeB, '2026-04-20', '2026-04-20', 'rejected', 'Beta rejected');

        return [$admin, $tenantA, $tenantB, $manager, $employeeUser];
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