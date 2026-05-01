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

class UsersExportXlsxTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_hr_can_export_users_xlsx_with_active_filters(): void
    {
        Carbon::setTestNow('2026-04-17 08:00:00');
        Excel::fake();

        $tenantA = $this->makeTenant('users-xlsx-tenant-a');
        $tenantB = $this->makeTenant('users-xlsx-tenant-b');
        $admin = $this->makeUser('admin_hr', $tenantA, 'users-xlsx-admin@example.test', 'Users Xlsx Admin');
        $linkedEmployeeUser = $this->makeUser('employee', $tenantA, 'users-xlsx-linked@example.test', 'Users Xlsx Linked');
        $unlinkedEmployeeUser = $this->makeUser('employee', $tenantA, 'users-xlsx-standalone@example.test', 'Users Xlsx Standalone');
        $otherTenantLinked = $this->makeUser('employee', $tenantB, 'users-xlsx-other@example.test', 'Users Xlsx Other');

        Employee::create([
            'tenant_id' => $tenantA->id,
            'user_id' => $linkedEmployeeUser->id,
            'employee_code' => 'USR-XL-1',
            'name' => 'Users Xlsx Linked Employee',
            'email' => 'users-xlsx-linked-employee@example.test',
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $tenantB->id,
            'user_id' => $otherTenantLinked->id,
            'employee_code' => 'USR-XL-2',
            'name' => 'Users Xlsx Other Employee',
            'email' => 'users-xlsx-other-employee@example.test',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('users.export', [
            'tenant_id' => $tenantA->id,
            'role' => 'employee',
            'linked' => 'only',
            'format' => 'xlsx',
        ]));

        $response->assertOk();

        Excel::assertDownloaded('users-export_20260417_tenant-users-xlsx-tenant-a_role-employee_linked-only.xlsx', function (UsersExport $export) {
            $rows = $export->collection()->toArray();

            $this->assertCount(1, $rows);
            $this->assertSame('users-xlsx-linked@example.test', $rows[0]['email']);
            $this->assertSame('USR-XL-1', $rows[0]['linked_employee_code']);

            return true;
        });
    }

    public function test_manager_xlsx_export_remains_tenant_scoped(): void
    {
        Carbon::setTestNow('2026-04-17 08:00:00');
        Excel::fake();

        $tenantA = $this->makeTenant('users-xlsx-scope-a');
        $tenantB = $this->makeTenant('users-xlsx-scope-b');
        $manager = $this->makeUser('manager', $tenantA, 'users-xlsx-manager@example.test', 'Users Xlsx Manager');
        $tenantALinked = $this->makeUser('employee', $tenantA, 'users-xlsx-tenant-a@example.test', 'Users Xlsx Tenant A');
        $tenantBLinked = $this->makeUser('employee', $tenantB, 'users-xlsx-tenant-b@example.test', 'Users Xlsx Tenant B');

        Employee::create([
            'tenant_id' => $tenantA->id,
            'user_id' => $tenantALinked->id,
            'employee_code' => 'USR-XLA-1',
            'name' => 'Users Xlsx Tenant A Employee',
            'email' => 'users-xlsx-tenant-a-employee@example.test',
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $tenantB->id,
            'user_id' => $tenantBLinked->id,
            'employee_code' => 'USR-XLB-1',
            'name' => 'Users Xlsx Tenant B Employee',
            'email' => 'users-xlsx-tenant-b-employee@example.test',
            'status' => 'active',
        ]);

        $response = $this->actingAs($manager)->get(route('users.export', [
            'tenant_id' => $tenantB->id,
            'role' => 'employee',
            'linked' => 'only',
            'format' => 'xlsx',
        ]));

        $response->assertOk();

        Excel::assertDownloaded('users-export_20260417_tenant-users-xlsx-scope-a_role-employee_linked-only.xlsx', function (UsersExport $export) {
            $rows = $export->collection()->toArray();

            $this->assertCount(1, $rows);
            $this->assertSame('users-xlsx-tenant-a@example.test', $rows[0]['email']);

            return true;
        });
    }

    public function test_employee_cannot_export_users_xlsx(): void
    {
        $tenant = $this->makeTenant('users-xlsx-forbidden');
        $employee = $this->makeUser('employee', $tenant, 'users-xlsx-employee@example.test', 'Users Xlsx Employee');

        $this->actingAs($employee)
            ->get(route('users.export', ['format' => 'xlsx']))
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