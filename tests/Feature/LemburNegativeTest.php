<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Lembur;
use App\Models\LemburSetting;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LemburNegativeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesTableSeeder::class);
    }

    public function test_tenant_lain_tidak_bisa_approve_lembur(): void
    {
        $tenant1 = $this->makeTenant('tenant-js');
        $tenant2 = $this->makeTenant('tenant-other');

        $managerTenant2 = $this->makeUser($tenant2, 'manager@other.test', 'manager');
        $employeeTenant1 = $this->makeEmployee($tenant1, 'EMP-T1', 'Budi', 'budi@js.test');

        $lembur = Lembur::create([
            'tenant_id' => $tenant1->id,
            'employee_id' => $employeeTenant1->id,
            'waktu_mulai' => '2026-05-01 18:00:00',
            'waktu_selesai' => '2026-05-01 20:00:00',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($managerTenant2)->post(route('lembur.approve', $lembur));

        $response->assertStatus(403);
    }

    public function test_validasi_waktu_selesai_harus_setelah_waktu_mulai(): void
    {
        $tenant = $this->makeTenant('tenant-validasi');
        $user = $this->makeUser($tenant, 'karyawan@js.test', 'employee');
        $employee = $this->makeEmployee($tenant, 'EMP-VAL', 'Budi', 'budi-validasi@js.test');
        $user->update(['employee_id' => $employee->id]);

        LemburSetting::create([
            'tenant_id' => $tenant->id,
            'role_pengaju' => 'karyawan',
            'butuh_persetujuan' => true,
            'tipe_tarif' => 'per_jam',
            'nilai_tarif' => 50000,
            'multiplier' => 1.5,
        ]);

        $response = $this->actingAs($user)->post(route('lembur.store'), [
            'employee_id' => $employee->id,
            'waktu_mulai' => '2026-05-01 21:00:00',
            'waktu_selesai' => '2026-05-01 20:00:00',
            'alasan' => 'Invalid test',
        ]);

        $response->assertSessionHasErrors('waktu_selesai');
    }

    public function test_karyawan_tidak_bisa_membuat_pengajuan_lembur_duplikat_di_tanggal_yang_sama(): void
    {
        $tenant = $this->makeTenant('tenant-duplikat');
        $user = $this->makeUser($tenant, 'duplikat@js.test', 'employee');
        $employee = $this->makeEmployee($tenant, 'EMP-DUP', 'Budi Duplikat', 'budi-duplikat@js.test');
        $user->update(['employee_id' => $employee->id]);

        LemburSetting::create([
            'tenant_id' => $tenant->id,
            'role_pengaju' => 'karyawan',
            'butuh_persetujuan' => true,
            'tipe_tarif' => 'per_jam',
            'nilai_tarif' => 50000,
            'multiplier' => 1.5,
        ]);

        Lembur::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'submitted_by' => $user->id,
            'pengaju' => 'karyawan',
            'waktu_mulai' => '2026-05-01 18:00:00',
            'waktu_selesai' => '2026-05-01 20:00:00',
            'durasi_jam' => 2,
            'status' => 'pending',
            'alasan' => 'Lembur pertama',
        ]);

        $response = $this->actingAs($user)->post(route('lembur.store'), [
            'employee_id' => $employee->id,
            'waktu_mulai' => '2026-05-01 21:00:00',
            'waktu_selesai' => '2026-05-01 23:00:00',
            'alasan' => 'Lembur kedua di hari sama',
        ]);

        $response->assertSessionHasErrors('waktu_mulai');
        $this->assertDatabaseCount('lemburs', 1);
    }

    public function test_setting_atasan_melarang_karyawan_mengajukan_lembur(): void
    {
        $tenant = $this->makeTenant('tenant-atasan-only');
        $user = $this->makeUser($tenant, 'employee-atasan-only@js.test', 'employee');
        $employee = $this->makeEmployee($tenant, 'EMP-ATS', 'Budi Atasan Only', 'budi-atasan-only@js.test');
        $user->update(['employee_id' => $employee->id]);

        LemburSetting::create([
            'tenant_id' => $tenant->id,
            'role_pengaju' => 'atasan',
            'butuh_persetujuan' => true,
            'tipe_tarif' => 'per_jam',
            'nilai_tarif' => 50000,
            'multiplier' => 1.5,
        ]);

        $response = $this->actingAs($user)->post(route('lembur.store'), [
            'employee_id' => $employee->id,
            'waktu_mulai' => '2026-05-09 18:00:00',
            'waktu_selesai' => '2026-05-09 20:00:00',
            'alasan' => 'Tidak boleh submit',
        ]);

        $response->assertRedirect(route('lembur.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('lemburs', 0);
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

    private function makeUser(Tenant $tenant, string $email, string $role): User
    {
        return User::create([
            'tenant_id' => $tenant->id,
            'role_id' => Role::idForSystemKey($role),
            'name' => ucfirst($role),
            'email' => $email,
            'password' => bcrypt('password'),
            'role' => $role,
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
}
