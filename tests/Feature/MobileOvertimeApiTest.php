<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Lembur;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileOvertimeApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
    }

    public function test_employee_can_list_and_submit_own_mobile_overtime(): void
    {
        $tenant = $this->makeTenant();
        [$user, $employee] = $this->makeEmployeeUser($tenant, 'self');
        [, $otherEmployee] = $this->makeEmployeeUser($tenant, 'other');

        Lembur::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'submitted_by' => $user->id,
            'pengaju' => 'karyawan',
            'waktu_mulai' => '2026-05-01 18:00:00',
            'waktu_selesai' => '2026-05-01 20:00:00',
            'durasi_jam' => 2,
            'status' => 'pending',
            'alasan' => 'Tutup laporan bulanan',
        ]);

        Lembur::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $otherEmployee->id,
            'submitted_by' => $otherEmployee->user_id,
            'pengaju' => 'karyawan',
            'waktu_mulai' => '2026-05-02 18:00:00',
            'waktu_selesai' => '2026-05-02 19:00:00',
            'durasi_jam' => 1,
            'status' => 'pending',
            'alasan' => 'Other employee',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/mobile/overtimes')
            ->assertOk()
            ->assertJsonPath('summary.total', 1)
            ->assertJsonPath('data.0.alasan', 'Tutup laporan bulanan');

        $this->postJson('/api/mobile/overtimes', [
            'waktu_mulai' => '2026-05-03 18:00:00',
            'waktu_selesai' => '2026-05-03 21:30:00',
            'alasan' => 'Closing operasional',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.durasi_jam', 3.5);

        $this->assertDatabaseHas('lemburs', [
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'alasan' => 'Closing operasional',
            'status' => 'pending',
        ]);
    }

    public function test_mobile_overtime_requires_submit_permission(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->makeEmployeeUser($tenant, 'blocked');

        RolePermission::query()
            ->where('role_id', $user->role_id)
            ->where('menu_key', 'lembur.submit')
            ->delete();

        Sanctum::actingAs($user);

        $this->getJson('/api/mobile/overtimes')->assertForbidden();
    }

    protected function makeTenant(): Tenant
    {
        return Tenant::create([
            'name' => 'Mobile Overtime Tenant',
            'slug' => 'mobile-overtime-tenant',
            'domain' => 'mobile-overtime-tenant.test',
            'status' => 'active',
        ]);
    }

    protected function makeEmployeeUser(Tenant $tenant, string $suffix): array
    {
        $role = Role::where('name', 'Employee')->firstOrFail();
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Mobile Overtime Employee '.$suffix,
            'email' => "mobile-overtime-{$suffix}@example.test",
            'password' => 'password123',
            'role_id' => $role->id,
            'role' => 'employee',
            'status' => 'active',
        ]);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'employee_code' => 'MOT-'.$suffix,
            'name' => $user->name,
            'email' => $user->email,
            'status' => 'active',
        ]);

        return [$user, $employee];
    }
}