<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileLeaveApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
    }

    public function test_employee_can_list_and_submit_own_mobile_leave(): void
    {
        $tenant = $this->makeTenant();
        [$user, $employee] = $this->makeEmployeeUser($tenant, 'self');
        [, $otherEmployee] = $this->makeEmployeeUser($tenant, 'other');
        $annualType = $this->makeLeaveType($tenant, 'Cuti Tahunan');

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $annualType->id,
            'start_date' => '2026-06-10',
            'end_date' => '2026-06-12',
            'reason' => 'Acara keluarga',
            'status' => 'pending',
            'approval_stage' => 'manager',
            'current_approval_role' => 'manager',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $otherEmployee->id,
            'leave_type_id' => $annualType->id,
            'start_date' => '2026-06-14',
            'end_date' => '2026-06-15',
            'reason' => 'Other employee',
            'status' => 'pending',
            'approval_stage' => 'manager',
            'current_approval_role' => 'manager',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/mobile/leaves')
            ->assertOk()
            ->assertJsonPath('summary.total', 1)
            ->assertJsonPath('data.0.reason', 'Acara keluarga')
            ->assertJsonPath('leave_types.0.name', 'Cuti Tahunan');

        $this->postJson('/api/mobile/leaves', [
            'leave_type_id' => $annualType->id,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-02',
            'reason' => 'Keperluan keluarga',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.duration_days', 2);

        $this->assertDatabaseHas('leaves', [
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $annualType->id,
            'reason' => 'Keperluan keluarga',
            'status' => 'pending',
        ]);
    }

    public function test_mobile_leave_requires_create_permission(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->makeEmployeeUser($tenant, 'blocked');

        RolePermission::query()
            ->where('role_id', $user->role_id)
            ->where('menu_key', 'leaves.create')
            ->delete();

        Sanctum::actingAs($user);

        $this->getJson('/api/mobile/leaves')->assertForbidden();
    }

    protected function makeTenant(): Tenant
    {
        return Tenant::create([
            'name' => 'Mobile Leave Tenant',
            'slug' => 'mobile-leave-tenant',
            'domain' => 'mobile-leave-tenant.test',
            'status' => 'active',
        ]);
    }

    protected function makeEmployeeUser(Tenant $tenant, string $suffix): array
    {
        $role = Role::where('name', 'Employee')->firstOrFail();
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Mobile Leave Employee '.$suffix,
            'email' => "mobile-leave-{$suffix}@example.test",
            'password' => 'password123',
            'role_id' => $role->id,
            'role' => 'employee',
            'status' => 'active',
        ]);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'employee_code' => 'MLE-'.$suffix,
            'name' => $user->name,
            'email' => $user->email,
            'status' => 'active',
        ]);

        return [$user, $employee];
    }

    protected function makeLeaveType(Tenant $tenant, string $name): LeaveType
    {
        return LeaveType::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'is_paid' => true,
            'wajib_lampiran' => false,
            'wajib_persetujuan' => true,
            'alur_persetujuan' => 'single',
        ]);
    }
}