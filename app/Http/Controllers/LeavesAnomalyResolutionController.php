<?php

namespace App\Http\Controllers;

use App\Services\LeavesAnomalyService;
use Illuminate\Http\Request;

class LeavesAnomalyResolutionController extends Controller
{
    public function __construct(protected LeavesAnomalyService $anomalyService)
    {
        $this->middleware('permission:leaves.manage');
    }

    public function index(Request $request)
    {
        $currentUser = $request->user() ?? auth()->user();
        $payload = $this->anomalyService->buildResolutionDashboardPayload(
            $currentUser,
            $request->integer('tenant_id'),
            $request->integer('month'),
            $request->integer('year')
        );

        return view('leaves.anomalies.resolutions', [
            'currentUser' => $currentUser,
            'summary' => $payload['summary'],
            'resolutions' => $payload['resolutions'],
            'tenants' => $payload['tenants'],
            'tenant' => $payload['tenant'],
            'canSwitchTenant' => $payload['canSwitchTenant'],
            'selectedMonth' => $payload['selectedMonth'],
            'selectedYear' => $payload['selectedYear'],
            'monthOptions' => $payload['monthOptions'],
            'yearOptions' => $payload['yearOptions'],
            'resolutionActions' => $payload['resolutionActions'],
            'tenantScopeLabel' => $payload['tenant']?->name ?? 'Tenant Tidak Tersedia',
            'tenantScopeDescription' => $payload['canSwitchTenant']
                ? 'Pilih tenant, bulan, dan tahun untuk memantau progres resolusi anomali cuti.'
                : 'Dashboard resolusi anomali dibatasi ke tenant aktif Anda.',
            'isTenantScoped' => ! $payload['canSwitchTenant'],
        ]);
    }

    public function trends(Request $request)
    {
        $currentUser = $request->user() ?? auth()->user();
        $payload = $this->anomalyService->buildResolutionTrendPayload(
            $currentUser,
            $request->integer('tenant_id'),
            $request->integer('year')
        );

        $monthly = $payload['monthly'];
        $annual = $payload['annual'];
        $actions = $payload['actions'];

        return view('leaves.anomalies.resolutions.trends', array_merge(compact('monthly', 'annual', 'actions'), [
            'currentUser' => $currentUser,
            'tenants' => $payload['tenants'],
            'tenant' => $payload['tenant'],
            'canSwitchTenant' => $payload['canSwitchTenant'],
            'summary' => $payload['summary'],
            'selectedYear' => $payload['selectedYear'],
            'selectedMonth' => $payload['selectedMonth'],
            'selectedMonthLabel' => $payload['selectedMonthLabel'],
            'yearOptions' => $payload['yearOptions'],
            'tenantScopeLabel' => $payload['tenant']?->name ?? 'Tenant Tidak Tersedia',
            'tenantScopeDescription' => $payload['canSwitchTenant']
                ? 'Pilih tenant dan tahun untuk memantau tren resolusi anomali cuti.'
                : 'Tren resolusi anomali dibatasi ke tenant aktif Anda.',
            'isTenantScoped' => ! $payload['canSwitchTenant'],
        ]));
    }

    public function log(Request $request)
    {
        $currentUser = $request->user() ?? auth()->user();
        $payload = $this->anomalyService->buildResolutionAuditLogPayload(
            $currentUser,
            $request->integer('tenant_id'),
            $request->integer('month'),
            $request->integer('year'),
            $request->string('search')->toString()
        );

        $logs = $payload['logs'];

        return view('leaves.anomalies.resolutions.log', compact('logs') + [
            'currentUser' => $currentUser,
            'summary' => $payload['summary'],
            'tenants' => $payload['tenants'],
            'tenant' => $payload['tenant'],
            'canSwitchTenant' => $payload['canSwitchTenant'],
            'selectedMonth' => $payload['selectedMonth'],
            'selectedYear' => $payload['selectedYear'],
            'search' => $payload['search'],
            'monthOptions' => $payload['monthOptions'],
            'yearOptions' => $payload['yearOptions'],
            'tenantScopeLabel' => $payload['tenant']?->name ?? 'Tenant Tidak Tersedia',
            'tenantScopeDescription' => $payload['canSwitchTenant']
                ? 'Pilih tenant, bulan, tahun, dan nama karyawan untuk meninjau jejak resolusi anomali cuti.'
                : 'Audit log resolusi anomali dibatasi ke tenant aktif Anda.',
            'isTenantScoped' => ! $payload['canSwitchTenant'],
        ]);
    }

    public function audit(Request $request)
    {
        $currentUser = $request->user() ?? auth()->user();
        $payload = $this->anomalyService->buildResolutionAuditDashboardPayload(
            $currentUser,
            $request->integer('tenant_id'),
            $request->integer('month'),
            $request->integer('year'),
            $request->string('search')->toString()
        );

        return view('leaves.anomalies.resolutions.audit', [
            'currentUser' => $currentUser,
            'summary' => $payload['summary'],
            'charts' => $payload['charts'],
            'logs' => $payload['logs'],
            'monthly' => $payload['monthly'],
            'annual' => $payload['annual'],
            'actions' => $payload['actions'],
            'tenants' => $payload['tenants'],
            'tenant' => $payload['tenant'],
            'canSwitchTenant' => $payload['canSwitchTenant'],
            'selectedMonth' => $payload['selectedMonth'],
            'selectedYear' => $payload['selectedYear'],
            'selectedMonthLabel' => $payload['selectedMonthLabel'],
            'search' => $payload['search'],
            'monthOptions' => $payload['monthOptions'],
            'yearOptions' => $payload['yearOptions'],
            'tenantScopeLabel' => $payload['tenant']?->name ?? 'Tenant Tidak Tersedia',
            'tenantScopeDescription' => $payload['canSwitchTenant']
                ? 'Pilih tenant, bulan, tahun, dan nama karyawan untuk memantau progres resolusi sekaligus jejak auditnya.'
                : 'Audit dashboard resolusi anomali dibatasi ke tenant aktif Anda.',
            'isTenantScoped' => ! $payload['canSwitchTenant'],
        ]);
    }

    public function store(Request $request, string $id)
    {
        $validated = $request->validate([
            'resolution_note' => ['required', 'string', 'max:2000'],
            'resolution_action' => ['required', 'string', 'in:Investigasi,Teguran,Disetujui Khusus,Abaikan'],
        ]);

        $currentUser = $request->user() ?? auth()->user();
        $this->anomalyService->resolveNotification($currentUser, $id, $validated);

        return back()->with('success', 'Resolusi anomali cuti berhasil disimpan.');
    }
}