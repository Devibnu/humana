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

class LemburFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesTableSeeder::class);
    }

    public function test_karyawan_bisa_mengajukan_lembur(): void
    {
        $tenant = $this->makeTenant('js-tenant-a');

        LemburSetting::create([
            'tenant_id' => $tenant->id,
            'role_pengaju' => 'karyawan',
            'butuh_persetujuan' => true,
            'tipe_tarif' => 'per_jam',
            'nilai_tarif' => 50000,
            'multiplier' => 1.5,
            'catatan' => 'Default aturan lembur tenant',
        ]);

        $user = $this->makeUser($tenant, 'pegawai-a@humana.test', 'employee');
        $employee = $this->makeEmployee($tenant, 'EMP-A', 'Budi A', 'budi-a@humana.test');
        $user->update(['employee_id' => $employee->id]);

        $response = $this->actingAs($user)->post(route('lembur.store'), [
            'employee_id' => $employee->id,
            'waktu_mulai' => '2026-05-01 18:00:00',
            'waktu_selesai' => '2026-05-01 21:00:00',
            'alasan' => 'Project deadline',
        ]);

        $response->assertRedirect(route('lembur.index'));

        $this->assertDatabaseHas('lemburs', [
            'employee_id' => $employee->id,
            'submitted_by' => $user->id,
            'status' => 'pending',
            'alasan' => 'Project deadline',
            'pengaju' => 'karyawan',
        ]);
    }

    public function test_pengaju_bisa_membuka_halaman_daftar_lembur(): void
    {
        $tenant = $this->makeTenant('js-tenant-index');
        $user = $this->makeUser($tenant, 'pegawai-index@humana.test', 'employee');
        $employee = $this->makeEmployee($tenant, 'EMP-IDX', 'Budi Index', 'budi-index@humana.test');
        $user->update(['employee_id' => $employee->id]);

        $response = $this->actingAs($user)->get(route('lembur.index'));

        $response->assertOk();
        $response->assertSee('Daftar Pengajuan Lembur');
    }

    public function test_halaman_pengajuan_hanya_menampilkan_lembur_pengaju_sendiri(): void
    {
        $tenant = $this->makeTenant('js-tenant-own-list');
        $user = $this->makeUser($tenant, 'pegawai-own@humana.test', 'employee');
        $otherUser = $this->makeUser($tenant, 'pegawai-other@humana.test', 'employee');
        $employeeA = $this->makeEmployee($tenant, 'EMP-OWN', 'Budi Own', 'budi-own@humana.test');
        $employeeB = $this->makeEmployee($tenant, 'EMP-OTH', 'Budi Other', 'budi-other@humana.test');
        $user->update(['employee_id' => $employeeA->id]);
        $otherUser->update(['employee_id' => $employeeB->id]);

        Lembur::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employeeA->id,
            'submitted_by' => $user->id,
            'pengaju' => 'karyawan',
            'waktu_mulai' => '2026-05-01 18:00:00',
            'waktu_selesai' => '2026-05-01 20:00:00',
            'durasi_jam' => 2,
            'status' => 'pending',
            'alasan' => 'Pengajuan saya',
        ]);

        Lembur::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employeeB->id,
            'submitted_by' => $otherUser->id,
            'pengaju' => 'karyawan',
            'waktu_mulai' => '2026-05-02 18:00:00',
            'waktu_selesai' => '2026-05-02 20:00:00',
            'durasi_jam' => 2,
            'status' => 'pending',
            'alasan' => 'Pengajuan orang lain',
        ]);

        $response = $this->actingAs($user)->get(route('lembur.index'));

        $response->assertOk();
        $response->assertSee('Pengajuan saya');
        $response->assertDontSee('Pengajuan orang lain');
    }

    public function test_halaman_approval_hanya_menampilkan_pengajuan_pending(): void
    {
        $tenant = $this->makeTenant('js-tenant-approval-list');
        $manager = $this->makeUser($tenant, 'manager-approval@humana.test', 'manager');
        $employee = $this->makeEmployee($tenant, 'EMP-APR', 'Budi Approval', 'budi-approval@humana.test');

        Lembur::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'pengaju' => 'karyawan',
            'waktu_mulai' => '2026-05-03 18:00:00',
            'waktu_selesai' => '2026-05-03 20:00:00',
            'durasi_jam' => 2,
            'status' => 'pending',
            'alasan' => 'Menunggu approval',
        ]);

        Lembur::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'pengaju' => 'karyawan',
            'waktu_mulai' => '2026-05-04 18:00:00',
            'waktu_selesai' => '2026-05-04 20:00:00',
            'durasi_jam' => 2,
            'status' => 'disetujui',
            'alasan' => 'Sudah diproses',
        ]);

        $response = $this->actingAs($manager)->get(route('lembur.approval'));

        $response->assertOk();
        $response->assertSee('Menunggu approval');
        $response->assertDontSee('Sudah diproses');
    }

    public function test_admin_hr_melihat_tombol_aksi_di_halaman_approval(): void
    {
        $tenant = $this->makeTenant('js-tenant-hr-approval');
        $adminHr = $this->makeUser($tenant, 'hr-approval@humana.test', 'admin_hr');
        $employee = $this->makeEmployee($tenant, 'EMP-HR', 'Budi HR', 'budi-hr@humana.test');

        Lembur::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'pengaju' => 'karyawan',
            'waktu_mulai' => '2026-05-11 18:00:00',
            'waktu_selesai' => '2026-05-11 20:00:00',
            'durasi_jam' => 2,
            'status' => 'pending',
            'alasan' => 'Approval HR',
        ]);

        $response = $this->actingAs($adminHr)->get(route('lembur.approval'));

        $response->assertOk();
        $response->assertSee('Setujui');
        $response->assertSee('Tolak');
    }

    public function test_admin_hr_bisa_menyetujui_lembur(): void
    {
        $tenant = $this->makeTenant('js-tenant-hr-approve');
        $adminHr = $this->makeUser($tenant, 'hr-approve@humana.test', 'admin_hr');
        $employee = $this->makeEmployee($tenant, 'EMP-HRA', 'Budi HRA', 'budi-hra@humana.test');

        $lembur = Lembur::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'waktu_mulai' => '2026-05-12 18:00:00',
            'waktu_selesai' => '2026-05-12 20:00:00',
            'durasi_jam' => 2,
            'status' => 'pending',
            'alasan' => 'Approval oleh HR',
        ]);

        $response = $this->actingAs($adminHr)->post(route('lembur.approve', $lembur));

        $response->assertRedirect();
        $this->assertDatabaseHas('lemburs', [
            'id' => $lembur->id,
            'status' => 'disetujui',
            'approver_id' => $adminHr->id,
        ]);
    }

    public function test_setting_atasan_mengizinkan_manager_mengajukan_lembur(): void
    {
        $tenant = $this->makeTenant('js-tenant-manager-submit');

        LemburSetting::create([
            'tenant_id' => $tenant->id,
            'role_pengaju' => 'atasan',
            'butuh_persetujuan' => true,
            'tipe_tarif' => 'per_jam',
            'nilai_tarif' => 50000,
            'multiplier' => 1.5,
        ]);

        $manager = $this->makeUser($tenant, 'manager-submit@humana.test', 'manager');
        $employee = $this->makeEmployee($tenant, 'EMP-MGR', 'Budi Tim', 'budi-tim@humana.test');

        $response = $this->actingAs($manager)->post(route('lembur.store'), [
            'employee_id' => $employee->id,
            'waktu_mulai' => '2026-05-08 18:00:00',
            'waktu_selesai' => '2026-05-08 20:00:00',
            'alasan' => 'Diajukan atasan',
        ]);

        $response->assertRedirect(route('lembur.index'));
        $this->assertDatabaseHas('lemburs', [
            'employee_id' => $employee->id,
            'submitted_by' => $manager->id,
            'pengaju' => 'atasan',
        ]);
    }

    public function test_manager_bisa_menyetujui_lembur(): void
    {
        $tenant = $this->makeTenant('js-tenant-b');
        $manager = $this->makeUser($tenant, 'manager-b@humana.test', 'manager');
        $employee = $this->makeEmployee($tenant, 'EMP-B', 'Budi B', 'budi-b@humana.test');

        $lembur = Lembur::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'waktu_mulai' => '2026-05-01 18:00:00',
            'waktu_selesai' => '2026-05-01 21:00:00',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($manager)->post(route('lembur.approve', $lembur));

        $response->assertRedirect();
        $this->assertDatabaseHas('lemburs', [
            'id' => $lembur->id,
            'status' => 'disetujui',
            'approver_id' => $manager->id,
        ]);
    }

    public function test_manager_bisa_menolak_lembur(): void
    {
        $tenant = $this->makeTenant('js-tenant-c');
        $manager = $this->makeUser($tenant, 'manager-c@humana.test', 'manager');
        $employee = $this->makeEmployee($tenant, 'EMP-C', 'Budi C', 'budi-c@humana.test');

        $lembur = Lembur::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'waktu_mulai' => '2026-05-01 18:00:00',
            'waktu_selesai' => '2026-05-01 21:00:00',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($manager)->post(route('lembur.reject', $lembur));

        $response->assertRedirect();
        $this->assertDatabaseHas('lemburs', [
            'id' => $lembur->id,
            'status' => 'ditolak',
            'approver_id' => $manager->id,
        ]);
    }

    public function test_export_lembur_menghasilkan_file_excel(): void
    {
        $tenant = $this->makeTenant('js-tenant-d');
        $user = $this->makeUser($tenant, 'pegawai-d@humana.test', 'admin_hr');
        $employee = $this->makeEmployee($tenant, 'EMP-D', 'Budi D', 'budi-d@humana.test');

        Lembur::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'waktu_mulai' => '2026-05-01 18:00:00',
            'waktu_selesai' => '2026-05-01 21:00:00',
            'status' => 'disetujui',
        ]);

        $response = $this->actingAs($user)->get(route('lembur.export'));

        $response->assertStatus(200);
        $response->assertHeader('content-disposition', 'attachment; filename=lembur-report-waktu-mulai-desc.xlsx');
    }

    public function test_export_lembur_pdf_menghasilkan_file_pdf(): void
    {
        $tenant = $this->makeTenant('js-tenant-d-pdf');
        $user = $this->makeUser($tenant, 'pegawai-d-pdf@humana.test', 'admin_hr');
        $employee = $this->makeEmployee($tenant, 'EMP-DP', 'Budi DP', 'budi-dp@humana.test');

        Lembur::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'waktu_mulai' => '2026-05-02 18:00:00',
            'waktu_selesai' => '2026-05-02 21:00:00',
            'status' => 'disetujui',
        ]);

        $response = $this->actingAs($user)->get(route('lembur.export.pdf'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_halaman_laporan_lembur_menampilkan_analitik_dan_tabel(): void
    {
        $tenant = $this->makeTenant('js-tenant-report');
        $user = $this->makeUser($tenant, 'report-admin@humana.test', 'admin_hr');
        $employee = $this->makeEmployee($tenant, 'EMP-RPT', 'Budi Report', 'budi-report@humana.test');

        Lembur::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'pengaju' => 'karyawan',
            'waktu_mulai' => '2026-05-03 18:00:00',
            'waktu_selesai' => '2026-05-03 21:00:00',
            'durasi_jam' => 3,
            'status' => 'disetujui',
            'alasan' => 'Deploy malam',
        ]);

        $response = $this->actingAs($user)->get(route('lembur.reports'));

        $response->assertOk();
        $response->assertSee('Laporan Lembur');
        $response->assertSee('Approval Rate');
        $response->assertSee('Analitik Persetujuan');
        $response->assertSee('Tren Jam dan Pengajuan Lembur');
        $response->assertSee('Top Karyawan Lembur');
        $response->assertSee('Preset Export Cepat');
        $response->assertSee('Sorting aktif: Tanggal Lembur DESC');
        $response->assertSee('Budi Report');
        $response->assertSee('Deploy malam');
    }

    public function test_halaman_laporan_lembur_menampilkan_shortcut_export_cepat(): void
    {
        $tenant = $this->makeTenant('js-tenant-report-export-shortcut');
        $user = $this->makeUser($tenant, 'report-export-shortcut@humana.test', 'admin_hr');
        $employee = $this->makeEmployee($tenant, 'EMP-RES', 'Budi Shortcut', 'budi-shortcut@humana.test');

        Lembur::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'pengaju' => 'karyawan',
            'waktu_mulai' => now()->subDays(5)->setTime(18, 0)->toDateTimeString(),
            'waktu_selesai' => now()->subDays(5)->setTime(20, 0)->toDateTimeString(),
            'durasi_jam' => 2,
            'status' => 'pending',
            'alasan' => 'Pending 7 hari',
        ]);

        Lembur::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'pengaju' => 'atasan',
            'waktu_mulai' => now()->subDays(6)->setTime(18, 0)->toDateTimeString(),
            'waktu_selesai' => now()->subDays(6)->setTime(21, 0)->toDateTimeString(),
            'durasi_jam' => 3,
            'status' => 'pending',
            'alasan' => 'Backlog lebih dari 3 hari',
        ]);

        Lembur::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'pengaju' => 'karyawan',
            'waktu_mulai' => now()->startOfMonth()->addDays(1)->setTime(18, 0)->toDateTimeString(),
            'waktu_selesai' => now()->startOfMonth()->addDays(1)->setTime(20, 0)->toDateTimeString(),
            'durasi_jam' => 2,
            'status' => 'disetujui',
            'alasan' => 'Approved bulan ini',
        ]);

        $response = $this->actingAs($user)->get(route('lembur.reports', ['sort_by' => 'status', 'sort_order' => 'asc']));

        $response->assertOk();
        $response->assertSee('Sorting aktif: Status ASC');
        $response->assertSee('Export Pending 7 Hari');
        $response->assertSee('Export Backlog > 3 Hari');
        $response->assertSee('Export Backlog > 7 Hari');
        $response->assertSee('Export Approved Bulan Ini');
        $response->assertSee('Perlu Tindak Lanjut');
        $response->assertSee('Pending tertua');
        $response->assertSee('combined_preset=pending_last_7_days', false);
        $response->assertSee('combined_preset=pending_over_3_days', false);
        $response->assertSee('combined_preset=pending_over_7_days', false);
        $response->assertSee('combined_preset=approved_month_this', false);
        $response->assertSee('sort_by=waktu_mulai', false);
        $response->assertSee('sort_order=desc', false);
        $response->assertSee('1 data');
        $response->assertSee('2.00 jam');
        $response->assertSee('3.00 jam');
    }

    public function test_halaman_approval_menyorot_backlog_kritis_dan_menengah(): void
    {
        $tenant = $this->makeTenant('js-tenant-approval-backlog-highlight');
        $adminHr = $this->makeUser($tenant, 'approval-backlog@humana.test', 'admin_hr');
        $employee = $this->makeEmployee($tenant, 'EMP-ABH', 'Budi Backlog', 'budi-backlog@humana.test');

        Lembur::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'pengaju' => 'karyawan',
            'waktu_mulai' => now()->subDays(8)->setTime(18, 0)->toDateTimeString(),
            'waktu_selesai' => now()->subDays(8)->setTime(20, 0)->toDateTimeString(),
            'durasi_jam' => 2,
            'status' => 'pending',
            'alasan' => 'Backlog kritis',
        ]);

        Lembur::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'pengaju' => 'karyawan',
            'waktu_mulai' => now()->subDays(4)->setTime(18, 0)->toDateTimeString(),
            'waktu_selesai' => now()->subDays(4)->setTime(20, 0)->toDateTimeString(),
            'durasi_jam' => 2,
            'status' => 'pending',
            'alasan' => 'Backlog menengah',
        ]);

        $response = $this->actingAs($adminHr)->get(route('lembur.approval'));

        $response->assertOk();
        $response->assertSee('Pending 8 hari');
        $response->assertSee('Pending 4 hari');
        $response->assertSee('Backlog Kritis &gt; 7 Hari', false);
        $response->assertSee('Backlog &gt; 7 Hari', false);
    }

    public function test_halaman_approval_bisa_filter_backlog_lebih_dari_7_hari(): void
    {
        $tenant = $this->makeTenant('js-tenant-approval-backlog-filter');
        $adminHr = $this->makeUser($tenant, 'approval-backlog-filter@humana.test', 'admin_hr');
        $employee = $this->makeEmployee($tenant, 'EMP-ABF', 'Budi Filter', 'budi-filter@humana.test');

        Lembur::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'pengaju' => 'karyawan',
            'waktu_mulai' => now()->subDays(9)->setTime(18, 0)->toDateTimeString(),
            'waktu_selesai' => now()->subDays(9)->setTime(20, 0)->toDateTimeString(),
            'durasi_jam' => 2,
            'status' => 'pending',
            'alasan' => 'Masuk filter kritis',
        ]);

        Lembur::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'pengaju' => 'karyawan',
            'waktu_mulai' => now()->subDays(2)->setTime(18, 0)->toDateTimeString(),
            'waktu_selesai' => now()->subDays(2)->setTime(20, 0)->toDateTimeString(),
            'durasi_jam' => 2,
            'status' => 'pending',
            'alasan' => 'Di luar filter kritis',
        ]);

        $response = $this->actingAs($adminHr)->get(route('lembur.approval', ['backlog_filter' => 'over_7_days']));

        $response->assertOk();
        $response->assertSee('Filter: Backlog &gt; 7 Hari', false);
        $response->assertSee('Masuk filter kritis');
        $response->assertDontSee('Di luar filter kritis');
    }

    public function test_halaman_laporan_lembur_mendukung_preset_filter_cepat(): void
    {
        $tenant = $this->makeTenant('js-tenant-report-preset');
        $user = $this->makeUser($tenant, 'report-preset@humana.test', 'admin_hr');
        $employee = $this->makeEmployee($tenant, 'EMP-RPS', 'Budi Preset', 'budi-preset@humana.test');

        Lembur::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'pengaju' => 'karyawan',
            'waktu_mulai' => now()->subDays(2)->setTime(18, 0)->toDateTimeString(),
            'waktu_selesai' => now()->subDays(2)->setTime(20, 0)->toDateTimeString(),
            'durasi_jam' => 2,
            'status' => 'disetujui',
            'alasan' => 'Masuk preset',
        ]);

        Lembur::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'pengaju' => 'karyawan',
            'waktu_mulai' => now()->subDays(14)->setTime(18, 0)->toDateTimeString(),
            'waktu_selesai' => now()->subDays(14)->setTime(20, 0)->toDateTimeString(),
            'durasi_jam' => 2,
            'status' => 'ditolak',
            'alasan' => 'Di luar preset',
        ]);

        $response = $this->actingAs($user)->get(route('lembur.reports', ['preset' => 'last_7_days']));

        $response->assertOk();
        $response->assertSee('Preset: 7 Hari Terakhir');
        $response->assertSee('Masuk preset');
        $response->assertDontSee('Di luar preset');
    }

    public function test_halaman_laporan_lembur_mendukung_preset_30_hari_dan_kuartal(): void
    {
        $tenant = $this->makeTenant('js-tenant-report-range');
        $user = $this->makeUser($tenant, 'report-range@humana.test', 'admin_hr');
        $employee = $this->makeEmployee($tenant, 'EMP-RNG', 'Budi Range', 'budi-range@humana.test');

        Lembur::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'pengaju' => 'karyawan',
            'waktu_mulai' => now()->subDays(20)->setTime(18, 0)->toDateTimeString(),
            'waktu_selesai' => now()->subDays(20)->setTime(21, 0)->toDateTimeString(),
            'durasi_jam' => 3,
            'status' => 'pending',
            'alasan' => 'Masuk 30 hari',
        ]);

        $response = $this->actingAs($user)->get(route('lembur.reports', ['preset' => 'last_30_days']));

        $response->assertOk();
        $response->assertSee('Preset: 30 Hari Terakhir');
        $response->assertSee('Masuk 30 hari');
        $response->assertSee('Kuartal Ini');
    }

    public function test_halaman_approval_menampilkan_snapshot_dengan_link_ke_report_pending(): void
    {
        $tenant = $this->makeTenant('js-tenant-approval-snapshot');
        $adminHr = $this->makeUser($tenant, 'snapshot-approval@humana.test', 'admin_hr');
        $employee = $this->makeEmployee($tenant, 'EMP-SNP', 'Budi Snapshot', 'budi-snapshot@humana.test');

        Lembur::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'pengaju' => 'karyawan',
            'waktu_mulai' => now()->setTime(18, 0)->toDateTimeString(),
            'waktu_selesai' => now()->setTime(20, 0)->toDateTimeString(),
            'durasi_jam' => 2,
            'status' => 'pending',
            'alasan' => 'Pending snapshot',
        ]);

        $response = $this->actingAs($adminHr)->get(route('lembur.approval'));

        $response->assertOk();
        $response->assertSee('Snapshot Laporan Bulan Ini');
        $response->assertSee('Lihat Backlog > 3 Hari', false);
    }

    public function test_halaman_laporan_lembur_mendukung_preset_status_cepat(): void
    {
        $tenant = $this->makeTenant('js-tenant-report-status-preset');
        $user = $this->makeUser($tenant, 'report-status@humana.test', 'admin_hr');
        $employee = $this->makeEmployee($tenant, 'EMP-RST', 'Budi Status', 'budi-status@humana.test');

        Lembur::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'pengaju' => 'karyawan',
            'waktu_mulai' => now()->subDay()->setTime(18, 0)->toDateTimeString(),
            'waktu_selesai' => now()->subDay()->setTime(20, 0)->toDateTimeString(),
            'durasi_jam' => 2,
            'status' => 'pending',
            'alasan' => 'Status pending',
        ]);

        Lembur::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'pengaju' => 'karyawan',
            'waktu_mulai' => now()->subDays(2)->setTime(18, 0)->toDateTimeString(),
            'waktu_selesai' => now()->subDays(2)->setTime(20, 0)->toDateTimeString(),
            'durasi_jam' => 2,
            'status' => 'disetujui',
            'alasan' => 'Status approved',
        ]);

        $response = $this->actingAs($user)->get(route('lembur.reports', ['status_preset' => 'pending_only']));

        $response->assertOk();
        $response->assertSee('Preset Status: Pending Saja');
        $response->assertSee('Status pending');
        $response->assertDontSee('Status approved');
    }

    public function test_halaman_laporan_lembur_mendukung_preset_kombinasi_cepat(): void
    {
        $tenant = $this->makeTenant('js-tenant-report-combined-preset');
        $user = $this->makeUser($tenant, 'report-combined@humana.test', 'admin_hr');
        $employee = $this->makeEmployee($tenant, 'EMP-RCB', 'Budi Combined', 'budi-combined@humana.test');

        Lembur::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'pengaju' => 'karyawan',
            'waktu_mulai' => now()->subDays(2)->setTime(18, 0)->toDateTimeString(),
            'waktu_selesai' => now()->subDays(2)->setTime(20, 0)->toDateTimeString(),
            'durasi_jam' => 2,
            'status' => 'pending',
            'alasan' => 'Masuk kombinasi',
        ]);

        Lembur::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'pengaju' => 'karyawan',
            'waktu_mulai' => now()->subDays(10)->setTime(18, 0)->toDateTimeString(),
            'waktu_selesai' => now()->subDays(10)->setTime(20, 0)->toDateTimeString(),
            'durasi_jam' => 2,
            'status' => 'pending',
            'alasan' => 'Di luar kombinasi',
        ]);

        $response = $this->actingAs($user)->get(route('lembur.reports', ['combined_preset' => 'pending_last_7_days']));

        $response->assertOk();
        $response->assertSee('Preset Kombinasi: Pending 7 Hari Terakhir');
        $response->assertSee('Masuk kombinasi');
        $response->assertDontSee('Di luar kombinasi');
    }

    public function test_halaman_laporan_lembur_mendukung_sorting_durasi(): void
    {
        $tenant = $this->makeTenant('js-tenant-report-sort');
        $user = $this->makeUser($tenant, 'report-sort@humana.test', 'admin_hr');
        $employee = $this->makeEmployee($tenant, 'EMP-RSO', 'Budi Sort', 'budi-sort@humana.test');

        Lembur::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'pengaju' => 'karyawan',
            'waktu_mulai' => '2026-05-01 18:00:00',
            'waktu_selesai' => '2026-05-01 19:00:00',
            'durasi_jam' => 1,
            'status' => 'pending',
            'alasan' => 'Durasi pendek',
        ]);

        Lembur::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'pengaju' => 'karyawan',
            'waktu_mulai' => '2026-05-02 18:00:00',
            'waktu_selesai' => '2026-05-02 22:00:00',
            'durasi_jam' => 4,
            'status' => 'pending',
            'alasan' => 'Durasi panjang',
        ]);

        $response = $this->actingAs($user)->get(route('lembur.reports', ['sort_by' => 'durasi_jam', 'sort_order' => 'desc']));

        $response->assertOk();
        $response->assertSee('Urut: Durasi DESC');
        $response->assertSeeInOrder(['Durasi panjang', 'Durasi pendek']);
    }

    public function test_halaman_laporan_lembur_mendukung_sorting_pengaju(): void
    {
        $tenant = $this->makeTenant('js-tenant-report-sort-pengaju');
        $user = $this->makeUser($tenant, 'report-sort-pengaju@humana.test', 'admin_hr');
        $employee = $this->makeEmployee($tenant, 'EMP-RSP', 'Budi Pengaju', 'budi-pengaju@humana.test');

        Lembur::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'pengaju' => 'karyawan',
            'waktu_mulai' => '2026-05-01 18:00:00',
            'waktu_selesai' => '2026-05-01 20:00:00',
            'durasi_jam' => 2,
            'status' => 'pending',
            'alasan' => 'Pengaju karyawan',
        ]);

        Lembur::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'pengaju' => 'atasan',
            'waktu_mulai' => '2026-05-02 18:00:00',
            'waktu_selesai' => '2026-05-02 20:00:00',
            'durasi_jam' => 2,
            'status' => 'pending',
            'alasan' => 'Pengaju atasan',
        ]);

        $response = $this->actingAs($user)->get(route('lembur.reports', ['sort_by' => 'pengaju', 'sort_order' => 'desc']));

        $response->assertOk();
        $response->assertSee('Urut: Pengaju DESC');
        $response->assertSeeInOrder(['Pengaju karyawan', 'Pengaju atasan']);
    }

    public function test_halaman_laporan_lembur_mendukung_sorting_alasan(): void
    {
        $tenant = $this->makeTenant('js-tenant-report-sort-alasan');
        $user = $this->makeUser($tenant, 'report-sort-alasan@humana.test', 'admin_hr');
        $employee = $this->makeEmployee($tenant, 'EMP-RSA', 'Budi Alasan', 'budi-alasan@humana.test');

        Lembur::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'pengaju' => 'karyawan',
            'waktu_mulai' => '2026-05-01 18:00:00',
            'waktu_selesai' => '2026-05-01 20:00:00',
            'durasi_jam' => 2,
            'status' => 'pending',
            'alasan' => 'Zebra deployment',
        ]);

        Lembur::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'pengaju' => 'karyawan',
            'waktu_mulai' => '2026-05-02 18:00:00',
            'waktu_selesai' => '2026-05-02 20:00:00',
            'durasi_jam' => 2,
            'status' => 'pending',
            'alasan' => 'Audit akses',
        ]);

        $response = $this->actingAs($user)->get(route('lembur.reports', ['sort_by' => 'alasan', 'sort_order' => 'asc']));

        $response->assertOk();
        $response->assertSee('Urut: Alasan ASC');
        $response->assertSeeInOrder(['Audit akses', 'Zebra deployment']);
    }

    public function test_export_lembur_mengikuti_nama_file_filter_aktif(): void
    {
        $tenant = $this->makeTenant('js-tenant-report-export-name');
        $user = $this->makeUser($tenant, 'report-export-name@humana.test', 'admin_hr');
        $employee = $this->makeEmployee($tenant, 'EMP-REN', 'Budi Export', 'budi-export@humana.test');

        Lembur::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'pengaju' => 'karyawan',
            'waktu_mulai' => '2026-05-03 18:00:00',
            'waktu_selesai' => '2026-05-03 20:00:00',
            'durasi_jam' => 2,
            'status' => 'pending',
            'alasan' => 'Export filename',
        ]);

        $response = $this->actingAs($user)->get(route('lembur.export', [
            'preset' => 'month_this',
            'status_preset' => 'pending_only',
            'sort_by' => 'pengaju',
            'sort_order' => 'asc',
        ]));

        $response->assertOk();
        $this->assertStringContainsString(
            'lembur-report-month-this-pending-only-pending-pengaju-asc-',
            (string) $response->headers->get('content-disposition')
        );
    }

    private function makeTenant(string $slug): Tenant
    {
        return Tenant::create([
            'name' => strtoupper(str_replace('-', ' ', $slug)),
            'code' => strtoupper(substr($slug, 0, 6)),
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
