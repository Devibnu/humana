<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Lembur;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
    }

    public function test_karyawan_tanpa_permission_tidak_bisa_akses_route_lembur(): void
    {
        $tenant = $this->makeTenant('route-middleware-employee');
        $employeeUser = $this->makeUser($tenant, 'employee', 'employee-route@humana.test', 'Karyawan');
        $employee = $this->makeEmployee($tenant, 'EMP-RM1', 'Budi', 'budi-route@humana.test');
        $lembur = $this->makeLembur($tenant, $employee);

        $this->actingAs($employeeUser)->get(route('lembur.index'))->assertOk();
        $this->actingAs($employeeUser)->get(route('lembur.reports'))->assertForbidden();
        $this->actingAs($employeeUser)->post(route('lembur.approve', $lembur))->assertForbidden();
        $this->actingAs($employeeUser)->get(route('lembur.export'))->assertForbidden();
        $this->actingAs($employeeUser)->get(route('lembur.export.pdf'))->assertForbidden();
    }

    public function test_manager_dengan_permission_bisa_approve_tapi_tidak_bisa_export(): void
    {
        $tenant = $this->makeTenant('route-middleware-manager');
        $manager = $this->makeUser($tenant, 'manager', 'manager-route@humana.test', 'Manager');
        $employee = $this->makeEmployee($tenant, 'EMP-RM2', 'Budi Manager', 'budi-manager-route@humana.test');
        $lembur = $this->makeLembur($tenant, $employee);

        $this->actingAs($manager)->post(route('lembur.approve', $lembur))->assertRedirect();
        $this->assertDatabaseHas('lemburs', [
            'id' => $lembur->id,
            'status' => 'disetujui',
            'approver_id' => $manager->id,
        ]);

        $this->actingAs($manager)->get(route('lembur.reports'))->assertForbidden();
        $this->actingAs($manager)->get(route('lembur.export'))->assertForbidden();
        $this->actingAs($manager)->get(route('lembur.export.pdf'))->assertForbidden();
    }

    public function test_admin_hr_dengan_permission_full_bisa_akses_semua_route_lembur(): void
    {
        $tenant = $this->makeTenant('route-middleware-admin');
        $admin = $this->makeUser($tenant, 'admin_hr', 'admin-route@humana.test', 'Admin HR');
        $employee = $this->makeEmployee($tenant, 'EMP-RM3', 'Budi Admin', 'budi-admin-route@humana.test');
        $lembur = $this->makeLembur($tenant, $employee);

        $this->actingAs($admin)->get(route('lembur.index'))->assertOk();
    $this->actingAs($admin)->get(route('lembur.reports'))->assertOk();
        $this->actingAs($admin)->post(route('lembur.approve', $lembur))->assertRedirect();
        $this->actingAs($admin)->get(route('lembur.export'))->assertOk();
        $this->actingAs($admin)->get(route('lembur.export.pdf'))->assertOk();
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

    private function makeUser(Tenant $tenant, string $roleKey, string $email, string $name): User
    {
        return User::create([
            'tenant_id' => $tenant->id,
            'role_id' => Role::idForSystemKey($roleKey),
            'role' => $roleKey,
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
    }

    private function makeEmployee(Tenant $tenant, string $code, string $name, string $email): Employee
    {
        return Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => $code,
            'name' => $name,
            'email' => $email,
            'phone' => '081234567890',
            'status' => 'active',
        ]);
    }

    private function makeLembur(Tenant $tenant, Employee $employee): Lembur
    {
        return Lembur::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'waktu_mulai' => '2026-05-01 18:00:00',
            'waktu_selesai' => '2026-05-01 20:00:00',
            'durasi_jam' => 2,
            'status' => 'pending',
            'alasan' => 'Route middleware test',
        ]);
    }
}
