<?php

namespace Tests\Feature;

use App\Http\Middleware\MenuAccessMiddleware;
use App\Http\Middleware\PermissionMiddleware;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveWorkflowConfigurationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
        $this->withoutMiddleware([MenuAccessMiddleware::class, PermissionMiddleware::class]);
    }

    public function test_store_leave_requires_attachment_when_leave_type_configured(): void
    {
        $tenant = $this->makeTenant('leave-workflow-attachment');
        $user = $this->makeUser($tenant, 'employee', 'employee-workflow@humana.test');
        $employee = $this->makeEmployee($tenant, 'EMP-LW1', 'Karyawan Workflow', 'employee-workflow@employee.test', $user->id);

        $type = LeaveType::create([
            'tenant_id' => $tenant->id,
            'name' => 'Cuti Sakit Test',
            'is_paid' => true,
            'wajib_lampiran' => true,
            'wajib_persetujuan' => true,
            'alur_persetujuan' => 'single',
        ]);

        $response = $this->actingAs($user)->post(route('leaves.store'), [
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $type->id,
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-02',
            'reason' => 'Perlu istirahat',
        ]);

        $response->assertSessionHasErrors('attachment');
        $this->assertDatabaseCount('leaves', 0);
    }

    public function test_store_leave_auto_flow_sets_approved_status(): void
    {
        $tenant = $this->makeTenant('leave-workflow-auto');
        $user = $this->makeUser($tenant, 'employee', 'employee-auto@humana.test');
        $employee = $this->makeEmployee($tenant, 'EMP-LW2', 'Karyawan Auto', 'employee-auto@employee.test', $user->id);

        $type = LeaveType::create([
            'tenant_id' => $tenant->id,
            'name' => 'Izin Auto Test',
            'is_paid' => false,
            'wajib_lampiran' => false,
            'wajib_persetujuan' => false,
            'alur_persetujuan' => 'auto',
        ]);

        $response = $this->actingAs($user)->post(route('leaves.store'), [
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $type->id,
            'start_date' => '2026-05-10',
            'end_date' => '2026-05-10',
            'reason' => 'Urusan pribadi',
        ]);

        $response->assertRedirect(route('leaves.index'));

        $leave = Leave::first();
        $this->assertNotNull($leave);
        $this->assertSame('approved', $leave->status);
        $this->assertNull($leave->approval_stage);
        $this->assertNull($leave->current_approval_role);
    }

    public function test_multi_approval_leave_progresses_from_supervisor_to_manager_to_hr(): void
    {
        $tenant = $this->makeTenant('leave-workflow-multi');
        $employeeUser = $this->makeUser($tenant, 'employee', 'employee-multi@humana.test');
        $employee = $this->makeEmployee($tenant, 'EMP-LW3', 'Karyawan Multi', 'employee-multi@employee.test', $employeeUser->id);
        $supervisor = $this->makeUser($tenant, 'supervisor', 'supervisor-multi@humana.test');
        $manager = $this->makeUser($tenant, 'manager', 'manager-multi@humana.test');
        $hr = $this->makeUser($tenant, 'admin_hr', 'hr-multi@humana.test');

        $type = LeaveType::create([
            'tenant_id' => $tenant->id,
            'name' => 'Cuti Multi Test',
            'is_paid' => true,
            'wajib_lampiran' => false,
            'wajib_persetujuan' => true,
            'alur_persetujuan' => 'multi',
        ]);

        $this->actingAs($employeeUser)->post(route('leaves.store'), [
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $type->id,
            'start_date' => '2026-06-10',
            'end_date' => '2026-06-12',
            'reason' => 'Perlu cuti bertahap',
        ])->assertRedirect(route('leaves.index'));

        $leave = Leave::firstOrFail();

        $this->assertSame('pending', $leave->status);
        $this->assertSame('supervisor', $leave->approval_stage);
        $this->assertSame('supervisor', $leave->current_approval_role);

        $this->actingAs($supervisor)
            ->put(route('leaves.update', ['leaf' => $leave->id]), [
                'status' => 'approved',
            ])
            ->assertRedirect(route('leaves.index'));

        $leave->refresh();
        $this->assertSame('pending', $leave->status);
        $this->assertSame('manager', $leave->approval_stage);
        $this->assertSame('manager', $leave->current_approval_role);

        $this->actingAs($manager)
            ->put(route('leaves.update', ['leaf' => $leave->id]), [
                'status' => 'approved',
            ])
            ->assertRedirect(route('leaves.index'));

        $leave->refresh();
        $this->assertSame('pending', $leave->status);
        $this->assertSame('hr', $leave->approval_stage);
        $this->assertSame('hr', $leave->current_approval_role);

        $this->actingAs($hr)
            ->put(route('leaves.update', ['leaf' => $leave->id]), [
                'tenant_id' => $tenant->id,
                'employee_id' => $employee->id,
                'leave_type_id' => $type->id,
                'start_date' => '2026-06-10',
                'end_date' => '2026-06-12',
                'reason' => 'Perlu cuti bertahap',
                'status' => 'approved',
            ])
            ->assertRedirect(route('leaves.index'));

        $leave->refresh();
        $this->assertSame('approved', $leave->status);
        $this->assertNull($leave->approval_stage);
        $this->assertNull($leave->current_approval_role);

        $this->actingAs($manager)
            ->get(route('leaves.index'))
            ->assertSee('Tahap Persetujuan')
            ->assertSee('Approved');
    }

    private function makeTenant(string $slug): Tenant
    {
        $sanitized = strtoupper(preg_replace('/[^A-Z0-9]/', '', strtoupper($slug)) ?? '');
        $code = substr($sanitized, 0, 5) . substr(strtoupper(dechex(crc32($slug))), 0, 3);

        return Tenant::create([
            'name' => strtoupper(str_replace('-', ' ', $slug)),
            'code' => $code,
            'slug' => $slug,
            'domain' => $slug . '.test',
            'status' => 'active',
        ]);
    }

    private function makeUser(Tenant $tenant, string $roleKey, string $email): User
    {
        return User::create([
            'tenant_id' => $tenant->id,
            'role_id' => Role::idForSystemKey($roleKey),
            'role' => $roleKey,
            'name' => ucfirst($roleKey),
            'email' => $email,
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
    }

    private function makeEmployee(Tenant $tenant, string $code, string $name, string $email, ?int $userId = null): Employee
    {
        return Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $userId,
            'employee_code' => $code,
            'name' => $name,
            'email' => $email,
            'phone' => '081234567890',
            'status' => 'active',
        ]);
    }
}
