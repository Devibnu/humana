<?php

namespace App\Http\Controllers;

use App\Support\AttendanceAnalyticsReportBuilder;
use Illuminate\Http\Request;

class AttendancesAnalyticsController extends Controller
{
    public function __construct(protected AttendanceAnalyticsReportBuilder $reportBuilder)
    {
        $this->middleware('permission:attendances.manage')->only('index');
    }

    public function index(Request $request)
    {
        $currentUser = $request->user() ?? auth()->user();

        $report = $this->reportBuilder->build(
            $currentUser,
            $request->integer('year'),
            $request->integer('month'),
            $request->integer('tenant_id')
        );

        $tenant = $report['tenant'];
        $canSwitchTenant = (bool) $report['canSwitchTenant'];

        $tenantScopeLabel = $tenant?->name ?? 'Tenant Tidak Tersedia';

        $tenantScopeDescription = $canSwitchTenant
            ? 'Pilih tenant untuk melihat analitik absensi.'
            : 'Data analitik dibatasi ke tenant aktif Anda.';

        return view('attendances.analytics', [
            'currentUser' => $currentUser,
            'tenants' => $report['tenants'],
            'tenant' => $tenant,
            'canSwitchTenant' => $canSwitchTenant,
            'tenantScopeLabel' => $tenantScopeLabel,
            'tenantScopeDescription' => $tenantScopeDescription,
            'isTenantScoped' => ! $canSwitchTenant,
            ...$report,
        ]);
    }
}