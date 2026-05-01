<?php

namespace Tests\Feature;

use App\Exports\UsersExport;
use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class UsersExportCsvTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_hr_can_export_users_csv_with_active_filters(): void
    {
        Carbon::setTestNow('2026-04-17 08:00:00');
        Excel::fake();

        $tenantA = $this->makeTenant('users-export-tenant-a');
        $tenantB = $this->makeTenant('users-export-tenant-b');
        $admin = $this->makeUser('admin_hr', $tenantA, 'users-export-admin@example.test', 'Users Export Admin');
        $linkedEmployeeUser = $this->makeUser('employee', $tenantA, 'users-export-linked@example.test', 'Users Export Linked');
        $unlinkedEmployeeUser = $this->makeUser('employee', $tenantA, 'users-export-standalone@example.test', 'Users Export Standalone');
        $otherTenantLinked = $this->makeUser('employee', $tenantB, 'users-export-other-tenant@example.test', 'Users Export Other Tenant');

        Employee::create([
            'tenant_id' => $tenantA->id,
            'user_id' => $linkedEmployeeUser->id,
            'employee_code' => 'USR-EXP-1',
            'name' => 'Users Export Linked Employee',
            'email' => 'users-export-linked-employee@example.test',
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $tenantB->id,
            'user_id' => $otherTenantLinked->id,
            'employee_code' => 'USR-EXP-2',
            'name' => 'Users Export Other Tenant Employee',
            'email' => 'users-export-other-tenant-employee@example.test',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('users.export', [
            'tenant_id' => $tenantA->id,
            'role' => 'employee',
            'linked' => 'only',
        ]));

        $response->assertOk();

        Excel::assertDownloaded('users-export_20260417_tenant-users-export-tenant-a_role-employee_linked-only.csv', function (UsersExport $export) {
            $rows = $export->collection()->toArray();

            $this->assertCount(1, $rows);
            $this->assertSame('users-export-linked@example.test', $rows[0]['email']);
            $this->assertSame('USR-EXP-1', $rows[0]['linked_employee_code']);

            return true;
        });
    }

    public function test_manager_csv_export_remains_tenant_scoped(): void
    {
        Carbon::setTestNow('2026-04-17 08:00:00');
        Excel::fake();

        $tenantA = $this->makeTenant('users-export-scope-tenant-a');
        $tenantB = $this->makeTenant('users-export-scope-tenant-b');
        $manager = $this->makeUser('manager', $tenantA, 'users-export-manager@example.test', 'Users Export Manager');
        $tenantALinked = $this->makeUser('employee', $tenantA, 'users-export-tenant-a-linked@example.test', 'Users Export Tenant A Linked');
        $tenantBLinked = $this->makeUser('employee', $tenantB, 'users-export-tenant-b-linked@example.test', 'Users Export Tenant B Linked');

        Employee::create([
            'tenant_id' => $tenantA->id,
            'user_id' => $tenantALinked->id,
            'employee_code' => 'USR-EXP-A1',
            'name' => 'Users Export Tenant A Employee',
            'email' => 'users-export-tenant-a-employee@example.test',
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $tenantB->id,
            'user_id' => $tenantBLinked->id,
            'employee_code' => 'USR-EXP-B1',
            'name' => 'Users Export Tenant B Employee',
            'email' => 'users-export-tenant-b-employee@example.test',
            'status' => 'active',
        ]);

        $response = $this->actingAs($manager)->get(route('users.export', [
            'tenant_id' => $tenantB->id,
            'role' => 'employee',
            'linked' => 'only',
        ]));

        $response->assertOk();

        Excel::assertDownloaded('users-export_20260417_tenant-users-export-scope-tenant-a_role-employee_linked-only.csv', function (UsersExport $export) {
            $rows = $export->collection()->toArray();

            $this->assertCount(1, $rows);
            $this->assertSame('users-export-tenant-a-linked@example.test', $rows[0]['email']);

            return true;
        });
    }

    public function test_employee_cannot_export_users_csv(): void
    {
        $tenant = $this->makeTenant('users-export-employee-forbidden');
        $employee = $this->makeUser('employee', $tenant, 'users-export-employee@example.test', 'Users Export Employee');

        $this->actingAs($employee)
            ->get(route('users.export'))
            ->assertForbidden();
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
}