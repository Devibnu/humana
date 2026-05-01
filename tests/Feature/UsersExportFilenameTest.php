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

class UsersExportFilenameTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_export_filename_contains_tenant_name_date_and_filters(): void
    {
        Carbon::setTestNow('2026-04-17 08:00:00');
        Excel::fake();

        $tenant = Tenant::create([
            'name' => 'North HQ',
            'slug' => 'north-hq',
            'domain' => 'north-hq.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Filename Admin',
            'email' => 'filename-admin@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $employeeUser = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Filename Employee',
            'email' => 'filename-employee@example.test',
            'password' => 'password',
            'role' => 'employee',
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $employeeUser->id,
            'employee_code' => 'USR-FN-1',
            'name' => 'Filename Employee Record',
            'email' => 'filename-employee-record@example.test',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('users.export', [
            'tenant_id' => $tenant->id,
            'role' => 'employee',
            'linked' => 'only',
            'format' => 'xlsx',
        ]));

        $response->assertOk();

        Excel::assertDownloaded('users-export_20260417_tenant-north-hq_role-employee_linked-only.xlsx', function (UsersExport $export) {
            return true;
        });
    }
}