<?php

namespace App\Http\Controllers;

use App\Services\LeavesAnomalyService;
use App\Support\LeaveAnomalyReportBuilder;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class LeavesAnomalyController extends Controller
{
    public function __construct(
        protected LeaveAnomalyReportBuilder $reportBuilder,
        protected LeavesAnomalyService $anomalyService
    )
    {
        $this->middleware('permission:leaves.manage')->only(['index', 'trends', 'markRead', 'markUnread']);
    }

    public function index(Request $request)
    {
        $currentUser = $request->user() ?? auth()->user();
        $report = $this->anomalyService->buildDashboardPayload($currentUser, $request->integer('tenant_id'));

        return view('leaves.anomalies', [
            'currentUser' => $currentUser,
            'tenants' => $report['tenants'],
            'tenant' => $report['tenant'],
            'canSwitchTenant' => $report['canSwitchTenant'],
            'summary' => $report['summary'],
            'charts' => $report['charts'],
            'alerts' => $report['alerts'],
            'notifications' => $report['notifications'],
            'unreadNotificationsCount' => $report['unreadNotificationsCount'],
            'resolutionActions' => $report['resolutionActions'],
            'selectedYear' => $report['selectedYear'],
            'selectedMonth' => $report['selectedMonth'],
            'selectedMonthLabel' => $report['selectedMonthLabel'],
            'tenantScopeLabel' => $report['tenant']?->name ?? 'Tenant Tidak Tersedia',
            'tenantScopeDescription' => $report['canSwitchTenant']
                ? 'Pilih tenant untuk melihat anomali cuti.'
                : 'Data anomali cuti dibatasi ke tenant aktif Anda.',
            'isTenantScoped' => ! $report['canSwitchTenant'],
        ]);
    }

    public function trends(Request $request)
    {
        $currentUser = $request->user() ?? auth()->user();
        $report = $this->reportBuilder->buildTrendReport(
            $currentUser,
            $request->integer('tenant_id'),
            $request->integer('year')
        );

        return view('leaves.anomalies.trends', [
            'currentUser' => $currentUser,
            'tenants' => $report['tenants'],
            'tenant' => $report['tenant'],
            'canSwitchTenant' => $report['canSwitchTenant'],
            'monthly' => $report['monthly'],
            'annual' => $report['annual'],
            'charts' => $report['charts'],
            'summary' => $report['summary'],
            'selectedYear' => $report['selectedYear'],
            'selectedMonth' => $report['selectedMonth'],
            'yearOptions' => $report['yearOptions'],
            'tenantScopeLabel' => $report['tenant']?->name ?? 'Tenant Tidak Tersedia',
            'tenantScopeDescription' => $report['canSwitchTenant']
                ? 'Pilih tenant dan tahun untuk melihat tren anomali cuti.'
                : 'Data tren anomali cuti dibatasi ke tenant aktif Anda.',
            'isTenantScoped' => ! $report['canSwitchTenant'],
        ]);
    }

    public function markRead(Request $request, DatabaseNotification $notification)
    {
        $currentUser = $request->user() ?? auth()->user();
        $this->ensureNotificationBelongsToCurrentUser($notification, $currentUser?->id);

        $notification->markAsRead();

        return back()->with('success', 'Notifikasi anomali ditandai sudah dibaca.');
    }

    public function markUnread(Request $request, DatabaseNotification $notification)
    {
        $currentUser = $request->user() ?? auth()->user();
        $this->ensureNotificationBelongsToCurrentUser($notification, $currentUser?->id);

        $notification->forceFill(['read_at' => null])->save();

        return back()->with('success', 'Notifikasi anomali ditandai belum dibaca.');
    }

    protected function ensureNotificationBelongsToCurrentUser(DatabaseNotification $notification, $userId): void
    {
        abort_unless(
            (string) $notification->notifiable_id === (string) $userId
                && $notification->notifiable_type === auth()->user()::class,
            403
        );
    }
}