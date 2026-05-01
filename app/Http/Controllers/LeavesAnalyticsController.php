<?php

namespace App\Http\Controllers;

use App\Support\LeaveAnalyticsReportBuilder;
use Illuminate\Http\Request;

class LeavesAnalyticsController extends Controller
{
    public function __construct(protected LeaveAnalyticsReportBuilder $reportBuilder)
    {
        $this->middleware('permission:leaves.analytics')->only('index');
    }

    public function index(Request $request)
    {
        $currentUser = $request->user() ?? auth()->user();
        $report = $this->reportBuilder->build(
            $currentUser,
            $request->integer('tenant_id'),
            $request->integer('year'),
            $request->integer('month')
        );
        $tenant = $report['tenant'];
        $canSwitchTenant = (bool) $report['canSwitchTenant'];

        return view('leaves.analytics', [
            'currentUser' => $currentUser,
            'tenants' => $report['tenants'],
            'tenant' => $tenant,
            'summary' => $report['summary'],
            'monthly' => $report['monthly'],
            'annual' => $report['annual'],
            'leaveTypeBreakdown' => $report['leaveTypeBreakdown'] ?? [],
            'charts' => $report['charts'],
            'monthlySummary' => $report['monthly'],
            'canSwitchTenant' => $canSwitchTenant,
            'selectedYear' => $report['selectedYear'],
            'selectedMonth' => $report['selectedMonth'],
            'selectedMonthLabel' => $report['selectedMonthLabel'],
            'yearOptions' => $report['yearOptions'],
            'monthOptions' => $report['monthOptions'],
            'tenantScopeLabel' => $tenant?->name ?? 'Tenant Tidak Tersedia',
            'tenantScopeDescription' => $canSwitchTenant
                ? 'Pilih tenant untuk melihat analitik cuti.'
                : 'Data analitik cuti dibatasi ke scope akses Anda.',
            'isTenantScoped' => ! $canSwitchTenant,
        ]);
    }
}