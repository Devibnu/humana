<?php

namespace App\Services;

use App\Models\LeavesAnomalyResolution;
use App\Models\User;
use App\Notifications\LeavesAnomalyNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use App\Support\LeaveAnomalyReportBuilder;
use Carbon\Carbon;

class LeavesAnomalyService
{
    public function __construct(protected LeaveAnomalyReportBuilder $reportBuilder)
    {
    }

    public function buildExportPayload($currentUser, ?int $requestedTenantId = null): array
    {
        $report = $this->reportBuilder->build($currentUser, $requestedTenantId);
        $report = $this->decorateReportWithResolutions($report);

        return [
            ...$report,
            'summaryRows' => $this->buildSummaryRows($report),
            'detailRows' => $this->buildDetailRows($report),
            'generatedAt' => now(),
            'tenantSlug' => $this->buildTenantSlug($currentUser, $report['tenant']),
        ];
    }

    public function buildResolutionExportPayload($currentUser, ?int $requestedTenantId = null): array
    {
        $tenantContext = $this->reportBuilder->resolveTenantContext($currentUser, $requestedTenantId);
        $tenant = $tenantContext['tenant'];
        $notifications = $this->resolutionExportNotifications($currentUser, $tenant?->id);
        $rows = $this->buildResolutionExportRows($notifications);

        return [
            'tenant' => $tenant,
            'tenants' => $tenantContext['tenants'],
            'canSwitchTenant' => $tenantContext['can_switch_tenant'],
            'generatedAt' => now(),
            'tenantSlug' => $this->buildTenantSlug($currentUser, $tenant),
            'summary' => [
                'resolved' => (int) collect($rows)->where('status_resolusi', 'Resolved')->count(),
                'unresolved' => (int) collect($rows)->where('status_resolusi', 'Belum Diselesaikan')->count(),
                'total' => (int) count($rows),
            ],
            'rows' => $rows,
        ];
    }

    public function buildResolutionDashboardPayload($currentUser, ?int $requestedTenantId = null, ?int $requestedMonth = null, ?int $requestedYear = null): array
    {
        $now = now();
        $tenantContext = $this->reportBuilder->resolveTenantContext($currentUser, $requestedTenantId);
        $tenant = $tenantContext['tenant'];
        $selectedYear = $this->normalizeYearValue($requestedYear, (int) $now->format('Y'));
        $selectedMonth = $this->normalizeMonthValue($requestedMonth, (int) $now->format('n'));

        $allNotifications = $this->resolutionExportNotifications($currentUser, $tenant?->id);
        $filteredNotifications = $allNotifications->filter(function (DatabaseNotification $notification) use ($selectedMonth, $selectedYear) {
            return (int) data_get($notification->data, 'selected_month') === $selectedMonth
                && (int) data_get($notification->data, 'selected_year') === $selectedYear;
        })->values();

        $resolutions = $this->buildResolutionExportRows($filteredNotifications);

        return [
            'tenant' => $tenant,
            'tenants' => $tenantContext['tenants'],
            'canSwitchTenant' => $tenantContext['can_switch_tenant'],
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'monthOptions' => $this->monthOptions(),
            'yearOptions' => $this->resolutionYearOptions($allNotifications, $selectedYear),
            'summary' => [
                'unresolved' => (int) collect($resolutions)->where('status_key', 'open')->count(),
                'resolved' => (int) collect($resolutions)->where('status_key', 'resolved')->count(),
                'spike' => (int) collect($resolutions)->where('type_key', 'lonjakan')->count(),
                'recurring' => (int) collect($resolutions)->where('type_key', 'pola_berulang')->count(),
                'carry_over' => (int) collect($resolutions)->where('type_key', 'carry_over')->count(),
                'total' => (int) count($resolutions),
            ],
            'resolutions' => $resolutions,
            'resolutionActions' => $this->resolutionActions(),
        ];
    }

    public function buildResolutionTrendPayload($currentUser, ?int $requestedTenantId = null, ?int $requestedYear = null): array
    {
        $now = now();
        $tenantContext = $this->reportBuilder->resolveTenantContext($currentUser, $requestedTenantId);
        $tenant = $tenantContext['tenant'];
        $selectedYear = $this->normalizeYearValue($requestedYear, (int) $now->format('Y'));
        $selectedMonth = (int) $now->format('n');
        $notifications = $this->resolutionExportNotifications($currentUser, $tenant?->id);
        $monthly = $this->buildResolutionMonthlyTrend($notifications, $selectedYear, $selectedMonth);
        $annual = $this->buildResolutionAnnualTrend($notifications, $selectedYear);
        $actions = $this->buildResolutionActionDistribution($notifications, $selectedYear, $selectedMonth);

        return [
            'tenant' => $tenant,
            'tenants' => $tenantContext['tenants'],
            'canSwitchTenant' => $tenantContext['can_switch_tenant'],
            'selectedYear' => $selectedYear,
            'selectedMonth' => $selectedMonth,
            'selectedMonthLabel' => $this->monthOptions()[$selectedMonth] ?? '-',
            'yearOptions' => $this->resolutionYearOptions($notifications, $selectedYear),
            'monthly' => $monthly,
            'annual' => $annual,
            'actions' => $actions,
            'summary' => [
                'resolved_this_month' => (int) collect($actions)->sum('total'),
                'resolved_this_year' => (int) collect($annual)->firstWhere('year', $selectedYear)['total_resolved'] ?? 0,
                'unresolved_active' => (int) $notifications->filter(fn (DatabaseNotification $notification) => data_get($notification->data, 'resolution_status', 'open') === 'open')->count(),
            ],
        ];
    }

    public function buildResolutionAuditLogPayload($currentUser, ?int $requestedTenantId = null, ?int $requestedMonth = null, ?int $requestedYear = null, ?string $requestedSearch = null): array
    {
        $now = now();
        $tenantContext = $this->reportBuilder->resolveTenantContext($currentUser, $requestedTenantId);
        $tenant = $tenantContext['tenant'];
        $selectedYear = $this->normalizeYearValue($requestedYear, (int) $now->format('Y'));
        $selectedMonth = $this->normalizeMonthValue($requestedMonth, (int) $now->format('n'));
        $search = trim((string) $requestedSearch);
        $metadataMap = $this->resolutionNotificationMetadataMap($currentUser, $tenant?->id);
        $allResolutions = LeavesAnomalyResolution::query()
            ->with('manager')
            ->when($metadataMap->isNotEmpty(), fn (Builder $builder) => $builder->whereIn('anomaly_id', $metadataMap->keys()->all()))
            ->when($metadataMap->isEmpty(), fn (Builder $builder) => $builder->whereRaw('1 = 0'))
            ->latest('resolved_at')
            ->get();

        $logs = $this->buildResolutionAuditLogRows($allResolutions, $metadataMap)
            ->filter(function (array $log) use ($selectedMonth, $selectedYear, $search) {
                $matchesPeriod = (int) $log['resolved_month'] === $selectedMonth && (int) $log['resolved_year'] === $selectedYear;

                if (! $matchesPeriod) {
                    return false;
                }

                if ($search === '') {
                    return true;
                }

                return str_contains(mb_strtolower($log['employee']), mb_strtolower($search));
            })
            ->values()
            ->all();

        return [
            'tenant' => $tenant,
            'tenants' => $tenantContext['tenants'],
            'canSwitchTenant' => $tenantContext['can_switch_tenant'],
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'search' => $search,
            'monthOptions' => $this->monthOptions(),
            'yearOptions' => $this->resolutionAuditLogYearOptions($allResolutions, $selectedYear),
            'logsCollection' => $allResolutions,
            'logs' => $logs,
            'summary' => [
                'total' => count($logs),
            ],
        ];
    }

    public function buildResolutionAuditDashboardPayload($currentUser, ?int $requestedTenantId = null, ?int $requestedMonth = null, ?int $requestedYear = null, ?string $requestedSearch = null): array
    {
        $now = now();
        $tenantContext = $this->reportBuilder->resolveTenantContext($currentUser, $requestedTenantId);
        $tenant = $tenantContext['tenant'];
        $selectedYear = $this->normalizeYearValue($requestedYear, (int) $now->format('Y'));
        $selectedMonth = $this->normalizeMonthValue($requestedMonth, (int) $now->format('n'));
        $search = trim((string) $requestedSearch);
        $notifications = $this->resolutionExportNotifications($currentUser, $tenant?->id);
        $monthly = $this->buildResolutionMonthlyTrend($notifications, $selectedYear, $selectedMonth);
        $annual = $this->buildResolutionAnnualTrend($notifications, $selectedYear);
        $actions = $this->buildResolutionActionDistribution($notifications, $selectedYear, $selectedMonth);
        $auditLogPayload = $this->buildResolutionAuditLogPayload(
            $currentUser,
            $requestedTenantId,
            $selectedMonth,
            $selectedYear,
            $search
        );

        return [
            'tenant' => $tenant,
            'tenants' => $tenantContext['tenants'],
            'canSwitchTenant' => $tenantContext['can_switch_tenant'],
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'selectedMonthLabel' => $this->monthOptions()[$selectedMonth] ?? '-',
            'search' => $search,
            'monthOptions' => $this->monthOptions(),
            'yearOptions' => $this->resolutionAuditLogYearOptions($auditLogPayload['logsCollection'], $selectedYear),
            'summary' => [
                'resolved_this_month' => (int) collect($actions)->sum('total'),
                'resolved_this_year' => (int) (collect($annual)->firstWhere('year', $selectedYear)['total_resolved'] ?? 0),
                'unresolved_active' => (int) $notifications->filter(fn (DatabaseNotification $notification) => data_get($notification->data, 'resolution_status', 'open') === 'open')->count(),
                'total_logs' => (int) count($auditLogPayload['logs']),
            ],
            'charts' => [
                'monthlyTrend' => [
                    'labels' => array_values(array_map(fn (array $row) => $row['label'], $monthly)),
                    'resolved' => array_values(array_map(fn (array $row) => (int) $row['resolved'], $monthly)),
                    'unresolved' => array_values(array_map(fn (array $row) => (int) $row['unresolved'], $monthly)),
                    'totals' => array_values(array_map(fn (array $row) => (int) $row['total'], $monthly)),
                ],
                'annualTrend' => [
                    'labels' => array_values(array_map(fn (array $row) => (string) $row['year'], $annual)),
                    'investigasi' => array_values(array_map(fn (array $row) => (int) $row['investigasi'], $annual)),
                    'teguran' => array_values(array_map(fn (array $row) => (int) $row['teguran'], $annual)),
                    'disetujui_khusus' => array_values(array_map(fn (array $row) => (int) $row['disetujui_khusus'], $annual)),
                    'abaikan' => array_values(array_map(fn (array $row) => (int) $row['abaikan'], $annual)),
                    'totals' => array_values(array_map(fn (array $row) => (int) $row['total_resolved'], $annual)),
                ],
                'actionDistribution' => [
                    'labels' => array_values(array_map(fn (array $row) => $row['label'], $actions)),
                    'values' => array_values(array_map(fn (array $row) => (int) $row['total'], $actions)),
                    'percentages' => array_values(array_map(fn (array $row) => (float) $row['percentage'], $actions)),
                ],
            ],
            'monthly' => $monthly,
            'annual' => $annual,
            'actions' => $actions,
            'logs' => $auditLogPayload['logs'],
        ];
    }

    public function buildResolutionAuditLogExportPayload($currentUser, ?int $requestedTenantId = null, ?int $requestedMonth = null, ?int $requestedYear = null, ?string $requestedSearch = null): array
    {
        $payload = $this->buildResolutionAuditLogPayload(
            $currentUser,
            $requestedTenantId,
            $requestedMonth,
            $requestedYear,
            $requestedSearch
        );

        return [
            ...$payload,
            'generatedAt' => now(),
            'tenantSlug' => $this->buildTenantSlug($currentUser, $payload['tenant']),
        ];
    }

    public function buildResolutionAuditDashboardExportPayload($currentUser, ?int $requestedTenantId = null, ?int $requestedMonth = null, ?int $requestedYear = null, ?string $requestedSearch = null): array
    {
        $payload = $this->buildResolutionAuditDashboardPayload(
            $currentUser,
            $requestedTenantId,
            $requestedMonth,
            $requestedYear,
            $requestedSearch
        );

        return [
            ...$payload,
            'generatedAt' => now(),
            'tenantSlug' => $this->buildTenantSlug($currentUser, $payload['tenant']),
        ];
    }

    public function buildDashboardPayload(?User $currentUser, ?int $requestedTenantId = null): array
    {
        $report = $this->reportBuilder->build($currentUser, $requestedTenantId);

        $this->syncNotifications($report);
        $notifications = $this->enrichNotificationsWithResolutions(
            $this->dashboardNotificationsFor($currentUser, $report['tenant']?->id)
        );
        $report = $this->decorateReportWithResolutions($report);

        return [
            ...$report,
            'notifications' => $notifications,
            'unreadNotificationsCount' => (int) $notifications->whereNull('read_at')->count(),
            'resolutionActions' => $this->resolutionActions(),
        ];
    }

    public function resolutionActions(): array
    {
        return ['Investigasi', 'Teguran', 'Disetujui Khusus', 'Abaikan'];
    }

    public function buildResolutionFilename($currentUser, $tenant, string $extension): string
    {
        return 'leaves_anomaly_resolutions_'.$this->buildTenantSlug($currentUser, $tenant).'_'.now()->format('Ymd').'.'.$extension;
    }

    public function buildResolutionAuditLogFilename($currentUser, $tenant, string $extension): string
    {
        return 'leaves_anomaly_resolution_audit_log_'.$this->buildTenantSlug($currentUser, $tenant).'_'.now()->format('Ymd').'.'.$extension;
    }

    public function buildResolutionAuditDashboardFilename($currentUser, $tenant, string $extension): string
    {
        return 'leaves_anomaly_resolution_audit_dashboard_'.$this->buildTenantSlug($currentUser, $tenant).'_'.now()->format('Ymd').'.'.$extension;
    }

    public function resolveNotification(User $currentUser, string $notificationId, array $attributes): LeavesAnomalyResolution
    {
        abort_unless($currentUser->isAdminHr() || $currentUser->isManager(), 403);

        $notification = DatabaseNotification::query()
            ->where('type', LeavesAnomalyNotification::class)
            ->findOrFail($notificationId);

        abort_unless(data_get($notification->data, 'category') === 'leave_anomaly', 403);
        abort_if(
            $currentUser->isManager() && (int) data_get($notification->data, 'tenant_id') !== (int) $currentUser->tenant_id,
            403
        );

        $fingerprint = (string) data_get($notification->data, 'fingerprint');
        abort_if($fingerprint === '', 422, 'Fingerprint anomali tidak ditemukan.');

        $resolution = LeavesAnomalyResolution::query()->updateOrCreate(
            ['anomaly_id' => $fingerprint],
            [
                'manager_id' => $currentUser->id,
                'resolution_note' => $attributes['resolution_note'],
                'resolution_action' => $attributes['resolution_action'],
                'resolved_at' => now(),
            ]
        );

        $this->applyResolutionToTenantNotifications($notification, $resolution, $currentUser);

        return $resolution;
    }

    public function syncNotifications(array $report): void
    {
        $tenantId = $report['tenant']?->id;

        if (! $tenantId) {
            return;
        }

        $alerts = collect($report['alerts'] ?? []);

        if ($alerts->isEmpty()) {
            return;
        }

        $recipients = User::query()
            ->where('tenant_id', $tenantId)
            ->whereRoleKeys(['admin_hr', 'manager'])
            ->where('status', 'active')
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $existingFingerprints = $this->existingFingerprintsByRecipient($recipients);

        foreach ($alerts as $alert) {
            $payload = $this->buildNotificationPayload($report, $alert);

            foreach ($recipients as $recipient) {
                $fingerprints = $existingFingerprints[$recipient->id] ?? [];

                if (in_array($payload['fingerprint'], $fingerprints, true)) {
                    continue;
                }

                $recipient->notify(new LeavesAnomalyNotification($payload));
                $existingFingerprints[$recipient->id][] = $payload['fingerprint'];
            }
        }
    }

    public function dashboardNotificationsFor(?User $currentUser, ?int $tenantId = null): Collection
    {
        if (! $currentUser) {
            return collect();
        }

        return $currentUser->notifications()
            ->where('type', LeavesAnomalyNotification::class)
            ->latest()
            ->get()
            ->filter(function (DatabaseNotification $notification) use ($tenantId) {
                if ($tenantId === null) {
                    return true;
                }

                return (int) data_get($notification->data, 'tenant_id') === (int) $tenantId;
            })
            ->sortByDesc(fn (DatabaseNotification $notification) => $notification->created_at?->timestamp ?? 0)
            ->sortBy(fn (DatabaseNotification $notification) => $notification->read_at ? 1 : 0)
            ->take(12)
            ->values();
    }

    public function unreadNotificationsCountFor(?User $currentUser, ?int $tenantId = null): int
    {
        return (int) $this->dashboardNotificationsFor($currentUser, $tenantId)
            ->whereNull('read_at')
            ->count();
    }

    protected function buildSummaryRows(array $report): array
    {
        $alerts = collect($report['alerts']);

        return [
            [
                'jenis_anomali' => 'Lonjakan',
                'jumlah_kasus' => (int) ($report['summary']['spike_count'] ?? 0),
                'contoh' => $alerts->firstWhere('type', 'lonjakan')['description'] ?? 'Tidak ada kasus lonjakan.',
            ],
            [
                'jenis_anomali' => 'Pola Berulang',
                'jumlah_kasus' => (int) ($report['summary']['recurring_count'] ?? 0),
                'contoh' => $alerts->firstWhere('type', 'pola_berulang')['description'] ?? 'Tidak ada pola berulang.',
            ],
            [
                'jenis_anomali' => 'Carry-Over',
                'jumlah_kasus' => (int) ($report['summary']['carry_over_count'] ?? 0),
                'contoh' => $alerts->firstWhere('type', 'carry_over')['description'] ?? 'Tidak ada carry-over berlebih.',
            ],
        ];
    }

    protected function buildSummary(array $alerts): array
    {
        $collection = collect($alerts);

        return [
            'anomalies_this_month' => (int) $collection->where('active_this_month', true)->count(),
            'spike_count' => (int) $collection->where('type', 'lonjakan')->count(),
            'recurring_count' => (int) $collection->where('type', 'pola_berulang')->count(),
            'carry_over_count' => (int) $collection->where('type', 'carry_over')->count(),
            'resolved_count' => (int) $collection->where('resolution_status', 'resolved')->count(),
            'total_alerts' => (int) $collection->count(),
        ];
    }

    protected function buildDetailRows(array $report): array
    {
        return collect($report['alerts'])->map(function (array $alert) use ($report) {
            return [
                'employee' => $alert['employee_name'],
                'jenis_anomali' => $this->formatAlertType($alert['type']),
                'deskripsi' => $alert['description'],
                'periode' => $report['selectedMonthLabel'].' '.$report['selectedYear'],
                'status_resolusi' => $alert['resolution_status_label'] ?? 'Belum Diselesaikan',
                'tindakan_resolusi' => $alert['resolution_action'] ?? '-',
                'catatan_resolusi' => $alert['resolution_note'] ?? '-',
                'diselesaikan_pada' => $alert['resolved_at_label'] ?? '-',
                'type_key' => $alert['type'],
            ];
        })->all();
    }

    public function buildFilename($currentUser, $tenant, string $extension): string
    {
        return 'leaves_anomalies_'.$this->buildTenantSlug($currentUser, $tenant).'_'.now()->format('Ymd').'.'.$extension;
    }

    protected function buildTenantSlug($currentUser, $tenant): string
    {
        if ($currentUser?->isManager()) {
            return Str::slug($currentUser->tenant?->name ?? 'tenant-manager', '-');
        }

        return Str::slug($tenant?->name ?? $currentUser?->tenant?->name ?? 'tenant-admin', '-');
    }

    protected function formatAlertType(string $type): string
    {
        return match ($type) {
            'lonjakan' => 'Lonjakan',
            'pola_berulang' => 'Pola Berulang',
            'carry_over' => 'Carry-Over',
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }

    protected function existingFingerprintsByRecipient(Collection $recipients): array
    {
        return $recipients->mapWithKeys(function (User $recipient) {
            $fingerprints = $recipient->notifications()
                ->where('type', LeavesAnomalyNotification::class)
                ->latest()
                ->limit(100)
                ->get()
                ->map(fn (DatabaseNotification $notification) => data_get($notification->data, 'fingerprint'))
                ->filter()
                ->values()
                ->all();

            return [$recipient->id => $fingerprints];
        })->all();
    }

    protected function buildNotificationPayload(array $report, array $alert): array
    {
        $detectedAt = now();
        $fingerprint = $this->buildAlertFingerprint($report, $alert, $detectedAt);

        return [
            'tenant_id' => (int) ($report['tenant']?->id ?? 0),
            'tenant_name' => $report['tenant']?->name ?? 'Tenant Tidak Diketahui',
            'employee_name' => $alert['employee_name'],
            'anomaly_type' => $alert['type'],
            'anomaly_type_label' => $this->formatAlertType($alert['type']),
            'title' => $alert['title'],
            'description' => $alert['description'],
            'color' => $alert['color'] ?? $this->colorForType($alert['type']),
            'icon' => $alert['icon'] ?? $this->iconForType($alert['type']),
            'detected_at' => $detectedAt->toIso8601String(),
            'detected_at_label' => $detectedAt->translatedFormat('d M Y H:i'),
            'selected_month' => (int) ($report['selectedMonth'] ?? 0),
            'selected_month_label' => $report['selectedMonthLabel'] ?? null,
            'selected_year' => (int) ($report['selectedYear'] ?? $detectedAt->year),
            'fingerprint' => $fingerprint,
            'category' => 'leave_anomaly',
            'resolution_status' => 'open',
            'resolution_status_label' => 'Belum Diselesaikan',
            'dashboard_url' => route('leaves.anomalies', array_filter(['tenant_id' => $report['tenant']?->id])),
        ];
    }

    protected function buildAlertFingerprint(array $report, array $alert, ?Carbon $now = null): string
    {
        $now ??= now();

        return sha1(implode('|', [
            (string) ($report['tenant']?->id ?? 0),
            (string) ($alert['type'] ?? ''),
            (string) ($alert['employee_name'] ?? ''),
            (string) ($alert['title'] ?? ''),
            (string) ($alert['description'] ?? ''),
            (string) ($report['selectedYear'] ?? $now->year),
            (string) ($report['selectedMonth'] ?? $now->month),
        ]));
    }

    protected function decorateReportWithResolutions(array $report): array
    {
        $alerts = collect($report['alerts'] ?? []);
        $resolutionMap = $this->resolutionMapForAlerts($alerts, $report);
        $decoratedAlerts = $alerts
            ->map(function (array $alert) use ($report, $resolutionMap) {
                $fingerprint = $this->buildAlertFingerprint($report, $alert);

                return [
                    ...$alert,
                    'fingerprint' => $fingerprint,
                    ...$this->resolutionStatePayload($resolutionMap->get($fingerprint)),
                ];
            })
            ->all();

        return [
            ...$report,
            'alerts' => $decoratedAlerts,
            'summary' => $this->buildSummary($decoratedAlerts),
        ];
    }

    protected function resolutionMapForAlerts(Collection $alerts, array $report): Collection
    {
        $fingerprints = $alerts
            ->map(fn (array $alert) => $this->buildAlertFingerprint($report, $alert))
            ->filter()
            ->unique()
            ->values();

        return $this->resolutionMapForFingerprints($fingerprints);
    }

    protected function resolutionMapForFingerprints(Collection $fingerprints): Collection
    {
        if ($fingerprints->isEmpty()) {
            return collect();
        }

        return LeavesAnomalyResolution::query()
            ->with('manager')
            ->whereIn('anomaly_id', $fingerprints->all())
            ->get()
            ->keyBy('anomaly_id');
    }

    protected function enrichNotificationsWithResolutions(Collection $notifications): Collection
    {
        $resolutionMap = $this->resolutionMapForFingerprints(
            $notifications
                ->map(fn (DatabaseNotification $notification) => data_get($notification->data, 'fingerprint'))
                ->filter()
                ->unique()
                ->values()
        );

        return $notifications->map(function (DatabaseNotification $notification) use ($resolutionMap) {
            $fingerprint = (string) data_get($notification->data, 'fingerprint');
            $data = [
                ...$notification->data,
                ...$this->resolutionStatePayload($resolutionMap->get($fingerprint)),
            ];

            $notification->setAttribute('data', $data);

            return $notification;
        })->values();
    }

    protected function resolutionStatePayload(?LeavesAnomalyResolution $resolution): array
    {
        if (! $resolution) {
            return [
                'resolution_status' => 'open',
                'resolution_status_label' => 'Belum Diselesaikan',
                'resolution_note' => null,
                'resolution_action' => null,
                'resolved_at' => null,
                'resolved_at_label' => null,
                'resolved_by' => null,
                'resolution_tooltip' => null,
            ];
        }

        $resolvedAtLabel = optional($resolution->resolved_at)->translatedFormat('d M Y H:i');
        $resolvedBy = $resolution->manager?->name ?? 'Manager Tidak Diketahui';

        return [
            'resolution_status' => 'resolved',
            'resolution_status_label' => 'Resolved',
            'resolution_note' => $resolution->resolution_note,
            'resolution_action' => $resolution->resolution_action,
            'resolved_at' => optional($resolution->resolved_at)?->toIso8601String(),
            'resolved_at_label' => $resolvedAtLabel,
            'resolved_by' => $resolvedBy,
            'resolution_tooltip' => sprintf(
                'Resolved oleh %s pada %s | Tindakan: %s | Catatan: %s',
                $resolvedBy,
                $resolvedAtLabel ?? '-',
                $resolution->resolution_action,
                $resolution->resolution_note
            ),
        ];
    }

    protected function applyResolutionToTenantNotifications(DatabaseNotification $sourceNotification, LeavesAnomalyResolution $resolution, User $resolver): void
    {
        $tenantId = (int) data_get($sourceNotification->data, 'tenant_id');
        $fingerprint = (string) data_get($sourceNotification->data, 'fingerprint');

        $recipients = User::query()
            ->where('tenant_id', $tenantId)
            ->whereRoleKeys(['admin_hr', 'manager'])
            ->where('status', 'active')
            ->get();

        foreach ($recipients as $recipient) {
            $recipient->notifications()
                ->where('type', LeavesAnomalyNotification::class)
                ->get()
                ->filter(fn (DatabaseNotification $notification) => data_get($notification->data, 'fingerprint') === $fingerprint)
                ->each(function (DatabaseNotification $notification) use ($resolution, $recipient, $resolver) {
                    $notification->forceFill([
                        'data' => [
                            ...$notification->data,
                            ...$this->resolutionStatePayload($resolution),
                        ],
                        'read_at' => $recipient->is($resolver) ? now() : null,
                    ])->save();
                });
        }

        $this->notifyOtherRecipientsAboutResolution($recipients, $sourceNotification, $resolution, $resolver);
    }

    protected function notifyOtherRecipientsAboutResolution(Collection $recipients, DatabaseNotification $sourceNotification, LeavesAnomalyResolution $resolution, User $resolver): void
    {
        $payload = [
            ...$sourceNotification->data,
            ...$this->resolutionStatePayload($resolution),
            'title' => 'Resolusi anomali cuti',
            'description' => sprintf(
                '%s menindaklanjuti anomali %s untuk %s dengan tindakan %s.',
                $resolver->name,
                strtolower((string) data_get($sourceNotification->data, 'anomaly_type_label', 'anomali')),
                (string) data_get($sourceNotification->data, 'employee_name', 'karyawan'),
                $resolution->resolution_action
            ),
            'color' => 'success',
            'icon' => 'fas fa-circle-check',
            'category' => 'leave_anomaly_resolution',
            'detected_at' => optional($resolution->resolved_at)?->toIso8601String(),
            'detected_at_label' => optional($resolution->resolved_at)?->translatedFormat('d M Y H:i'),
        ];

        $recipients
            ->reject(fn (User $recipient) => $recipient->is($resolver))
            ->each(fn (User $recipient) => $recipient->notify(new LeavesAnomalyNotification($payload, ['mail', 'broadcast'])));
    }

    protected function colorForType(string $type): string
    {
        return match ($type) {
            'lonjakan' => 'danger',
            'pola_berulang' => 'warning',
            'carry_over' => 'info',
            'resolved' => 'success',
            default => 'secondary',
        };
    }

    protected function resolutionExportNotifications($currentUser, ?int $tenantId = null): Collection
    {
        $query = $this->resolutionBaseNotificationsQuery($currentUser, $tenantId)->latest();

        return $this->enrichNotificationsWithResolutions(
            $query->get()
                ->groupBy(fn (DatabaseNotification $notification) => (string) data_get($notification->data, 'fingerprint'))
                ->map(fn (Collection $group) => $group->sortByDesc('created_at')->first())
                ->values()
        );
    }

    protected function buildResolutionExportRows(Collection $notifications): array
    {
        return $notifications
            ->map(function (DatabaseNotification $notification) {
                $data = $notification->data;

                return [
                    'notification_id' => $notification->id,
                    'tenant_id' => (int) data_get($data, 'tenant_id', 0),
                    'tenant_name' => data_get($data, 'tenant_name', 'Tenant Tidak Diketahui'),
                    'selected_month' => (int) data_get($data, 'selected_month', 0),
                    'selected_year' => (int) data_get($data, 'selected_year', 0),
                    'employee' => data_get($data, 'employee_name', 'Karyawan Tidak Diketahui'),
                    'jenis_anomali' => data_get($data, 'anomaly_type_label', 'Anomali'),
                    'deskripsi' => data_get($data, 'description', '-'),
                    'periode' => trim((string) data_get($data, 'selected_month_label', '').' '.(string) data_get($data, 'selected_year', '')),
                    'manager' => data_get($data, 'resolved_by') ?: '-',
                    'tindakan' => data_get($data, 'resolution_action') ?: '-',
                    'catatan' => data_get($data, 'resolution_note') ?: '-',
                    'tanggal_resolusi' => data_get($data, 'resolved_at_label') ?: '-',
                    'status_resolusi' => data_get($data, 'resolution_status_label', 'Belum Diselesaikan'),
                    'resolution_tooltip' => data_get($data, 'resolution_tooltip') ?: 'Belum ada catatan resolusi.',
                    'type_key' => data_get($data, 'anomaly_type'),
                    'status_key' => data_get($data, 'resolution_status', 'open'),
                ];
            })
            ->sortBy([
                fn (array $row) => $row['status_key'] === 'open' ? 0 : 1,
                fn (array $row) => $row['employee'],
            ])
            ->values()
            ->all();
    }

    protected function buildResolutionAuditLogRows(Collection $resolutions, Collection $metadataMap): Collection
    {
        return $resolutions->map(function (LeavesAnomalyResolution $resolution) use ($metadataMap) {
            $notification = $metadataMap->get($resolution->anomaly_id);
            $data = $notification?->data ?? [];
            $selectedMonth = (int) data_get($data, 'selected_month', 0);
            $selectedYear = (int) data_get($data, 'selected_year', 0);
            $selectedMonthLabel = data_get($data, 'selected_month_label') ?: ($this->monthOptions()[$selectedMonth] ?? '-');
            $resolvedAtLabel = optional($resolution->resolved_at)->translatedFormat('d M Y H:i') ?: '-';

            return [
                'anomaly_id' => $resolution->anomaly_id,
                'tenant_id' => (int) data_get($data, 'tenant_id', $resolution->manager?->tenant_id ?? 0),
                'tenant_name' => data_get($data, 'tenant_name', $resolution->manager?->tenant?->name ?? 'Tenant Tidak Diketahui'),
                'employee' => data_get($data, 'employee_name', 'Karyawan Tidak Diketahui'),
                'jenis_anomali' => data_get($data, 'anomaly_type_label', 'Anomali'),
                'deskripsi' => data_get($data, 'description', '-'),
                'periode' => trim($selectedMonthLabel.' '.($selectedYear > 0 ? $selectedYear : '-')),
                'manager_id' => (int) $resolution->manager_id,
                'manager' => $resolution->manager?->name ?? 'Manager Tidak Diketahui',
                'tindakan' => $resolution->resolution_action,
                'catatan' => $resolution->resolution_note,
                'timestamp' => $resolvedAtLabel,
                'created_at_label' => optional($resolution->created_at)->translatedFormat('d M Y H:i') ?: '-',
                'updated_at_label' => optional($resolution->updated_at)->translatedFormat('d M Y H:i') ?: '-',
                'resolved_at_label' => $resolvedAtLabel,
                'resolved_month' => (int) optional($resolution->resolved_at)?->format('n'),
                'resolved_year' => (int) optional($resolution->resolved_at)?->format('Y'),
                'type_key' => data_get($data, 'anomaly_type'),
                'status_key' => 'resolved',
                'resolution_tooltip' => $resolution->resolution_note,
            ];
        });
    }

    protected function buildResolutionMonthlyTrend(Collection $notifications, int $selectedYear, int $selectedMonth): array
    {
        $endMonth = $selectedYear === (int) now()->format('Y') ? $selectedMonth : 12;
        $endDate = Carbon::create($selectedYear, $endMonth, 1)->endOfMonth();
        $startDate = $endDate->copy()->subMonths(11)->startOfMonth();
        $monthShortLabels = $this->resolutionMonthShortLabels();
        $period = collect();
        $cursor = $startDate->copy();

        while ($cursor->lessThanOrEqualTo($endDate)) {
            $period->push($cursor->copy());
            $cursor->addMonth();
        }

        return $period->map(function (Carbon $month) use ($notifications, $monthShortLabels) {
            $bucket = $notifications->filter(function (DatabaseNotification $notification) use ($month) {
                return (int) data_get($notification->data, 'selected_year') === (int) $month->format('Y')
                    && (int) data_get($notification->data, 'selected_month') === (int) $month->format('n');
            });

            $resolved = (int) $bucket->filter(fn (DatabaseNotification $notification) => data_get($notification->data, 'resolution_status', 'open') === 'resolved')->count();
            $unresolved = (int) $bucket->filter(fn (DatabaseNotification $notification) => data_get($notification->data, 'resolution_status', 'open') === 'open')->count();
            $total = $resolved + $unresolved;

            return [
                'label' => ($monthShortLabels[(int) $month->format('n')] ?? $month->format('M')).' '.$month->format('Y'),
                'short_label' => $monthShortLabels[(int) $month->format('n')] ?? $month->format('M'),
                'month' => (int) $month->format('n'),
                'year' => (int) $month->format('Y'),
                'resolved' => $resolved,
                'unresolved' => $unresolved,
                'total' => $total,
            ];
        })->values()->all();
    }

    protected function buildResolutionAnnualTrend(Collection $notifications, int $selectedYear): array
    {
        return collect(range($selectedYear - 4, $selectedYear))->map(function (int $year) use ($notifications) {
            $yearNotifications = $notifications->filter(function (DatabaseNotification $notification) use ($year) {
                $resolvedAt = data_get($notification->data, 'resolved_at');

                return $resolvedAt && Carbon::parse($resolvedAt)->year === $year;
            });

            $actions = collect($this->resolutionActions())->mapWithKeys(function (string $action) use ($yearNotifications) {
                return [$action => (int) $yearNotifications->where('data.resolution_action', $action)->count()];
            });

            return [
                'year' => $year,
                'investigasi' => (int) ($actions['Investigasi'] ?? 0),
                'teguran' => (int) ($actions['Teguran'] ?? 0),
                'disetujui_khusus' => (int) ($actions['Disetujui Khusus'] ?? 0),
                'abaikan' => (int) ($actions['Abaikan'] ?? 0),
                'total_resolved' => (int) $actions->sum(),
            ];
        })->values()->all();
    }

    protected function buildResolutionActionDistribution(Collection $notifications, int $selectedYear, int $selectedMonth): array
    {
        $monthNotifications = $notifications->filter(function (DatabaseNotification $notification) use ($selectedYear, $selectedMonth) {
            $resolvedAt = data_get($notification->data, 'resolved_at');

            return $resolvedAt
                && Carbon::parse($resolvedAt)->year === $selectedYear
                && Carbon::parse($resolvedAt)->month === $selectedMonth;
        });

        $total = (int) $monthNotifications->count();

        return collect($this->resolutionActions())->map(function (string $action) use ($monthNotifications, $total) {
            $count = (int) $monthNotifications->where('data.resolution_action', $action)->count();

            return [
                'label' => $action,
                'total' => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0.0,
            ];
        })->values()->all();
    }

    protected function resolutionNotificationMetadataMap($currentUser, ?int $tenantId = null): Collection
    {
        return $this->resolutionBaseNotificationsQuery($currentUser, $tenantId)
            ->latest()
            ->get()
            ->groupBy(fn (DatabaseNotification $notification) => (string) data_get($notification->data, 'fingerprint'))
            ->map(fn (Collection $group) => $group->sortByDesc('created_at')->first());
    }

    protected function resolutionBaseNotificationsQuery($currentUser, ?int $tenantId = null): Builder
    {
        return DatabaseNotification::query()
            ->where('type', LeavesAnomalyNotification::class)
            ->where('data->category', 'leave_anomaly')
            ->when($currentUser?->isManager(), fn (Builder $builder) => $builder->where('data->tenant_id', $currentUser->tenant_id))
            ->when($currentUser?->isAdminHr() && $tenantId, fn (Builder $builder) => $builder->where('data->tenant_id', $tenantId))
            ->when($currentUser?->isAdminHr() && ! $tenantId, fn (Builder $builder) => $builder->whereRaw('1 = 0'));
    }

    protected function resolutionYearOptions(Collection $notifications, int $selectedYear): array
    {
        return $notifications
            ->map(fn (DatabaseNotification $notification) => (int) data_get($notification->data, 'selected_year', $selectedYear))
            ->merge(range($selectedYear - 4, $selectedYear))
            ->push($selectedYear)
            ->filter(fn (int $year) => $year > 0)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    protected function resolutionAuditLogYearOptions(Collection $resolutions, int $selectedYear): array
    {
        return $resolutions
            ->map(fn (LeavesAnomalyResolution $resolution) => (int) optional($resolution->resolved_at)?->format('Y'))
            ->merge(range($selectedYear - 4, $selectedYear))
            ->push($selectedYear)
            ->filter(fn (int $year) => $year > 0)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    protected function monthOptions(): array
    {
        return [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];
    }

    protected function resolutionMonthShortLabels(): array
    {
        return [
            1 => 'Jan',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Apr',
            5 => 'Mei',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Agu',
            9 => 'Sep',
            10 => 'Okt',
            11 => 'Nov',
            12 => 'Des',
        ];
    }

    protected function normalizeMonthValue(?int $requestedMonth, int $defaultMonth): int
    {
        if ($requestedMonth === null || $requestedMonth < 1 || $requestedMonth > 12) {
            return $defaultMonth;
        }

        return $requestedMonth;
    }

    protected function normalizeYearValue(?int $requestedYear, int $defaultYear): int
    {
        if ($requestedYear === null || $requestedYear < 2000 || $requestedYear > 2100) {
            return $defaultYear;
        }

        return $requestedYear;
    }

    protected function iconForType(string $type): string
    {
        return match ($type) {
            'lonjakan' => 'fas fa-triangle-exclamation',
            'pola_berulang' => 'fas fa-arrows-rotate',
            'carry_over' => 'fas fa-calendar-check',
            default => 'fas fa-bell',
        };
    }
}