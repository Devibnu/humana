<?php

namespace App\Http\Controllers;

use App\Exports\LemburExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Employee;
use App\Models\Lembur;
use App\Models\LemburSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class LemburController extends Controller
{
    public function index(Request $request): View
    {
        return $this->renderListingPage($request, 'submission');
    }

    public function approval(Request $request): View
    {
        return $this->renderListingPage($request, 'approval');
    }

    public function reports(Request $request): View
    {
        $currentUser = $request->user() ?? auth()->user();
        $filters = $this->resolveReportFilters($request);
        $reportQuery = $this->applyReportSorting($this->buildReportQuery($currentUser, $filters), $filters);

        $reports = (clone $reportQuery)
            ->paginate($filters['per_page'])
            ->withQueryString();

        return view('lembur.reports', [
            'reports' => $reports,
            'filters' => $filters,
            'summary' => $this->buildReportSummary(clone $reportQuery),
            'statusBreakdown' => $this->buildReportStatusBreakdown(clone $reportQuery),
            'approvalInsights' => $this->buildApprovalInsights(clone $reportQuery),
            'topEmployees' => $this->buildTopEmployees(clone $reportQuery),
            'monthlyTrendChart' => $this->buildMonthlyTrendChart(clone $reportQuery),
            'statusDistributionChart' => $this->buildStatusDistributionChart(clone $reportQuery),
            'topEmployeesChart' => $this->buildTopEmployeesChart(clone $reportQuery),
            'statusOptions' => $this->reportStatusOptions(),
            'submissionRoleOptions' => $this->reportSubmissionRoleOptions(),
            'sortLabels' => $this->reportSortLabels(),
            'quickFilterPresets' => $this->reportQuickFilterPresets(),
            'quickStatusPresets' => $this->reportQuickStatusPresets(),
            'quickCombinedPresets' => $this->reportQuickCombinedPresets(),
            'quickExportPresets' => $this->buildQuickExportPresets($currentUser),
        ]);
    }

    public function create(Request $request): View
    {
        $currentUser = $request->user() ?? auth()->user();

        $settings = $this->resolveSettings($currentUser);
        $submissionRole = $this->resolveSubmissionRole($settings);
        $employees = $this->submissionEmployees($currentUser, $submissionRole);
        $submissionAccessIssue = $this->submissionAccessIssue($currentUser, $submissionRole, $employees);

        return view('lembur.create', [
            'settings' => $settings,
            'submissionRole' => $submissionRole,
            'submissionAccessIssue' => $submissionAccessIssue,
            'employees' => $employees,
            'selectedEmployeeId' => $employees->count() === 1 ? $employees->first()->id : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $currentUser = $request->user() ?? auth()->user();
        $settings = $this->resolveSettings($currentUser);
        $submissionRole = $this->resolveSubmissionRole($settings);
        $submissionEmployees = $this->submissionEmployees($currentUser, $submissionRole);
        $submissionAccessIssue = $this->submissionAccessIssue($currentUser, $submissionRole, $submissionEmployees);

        if ($submissionAccessIssue !== null) {
            return redirect()->route('lembur.index')->with('error', $submissionAccessIssue);
        }

        $validated = $request->validate([
            'employee_id' => [
                'required',
                Rule::exists('employees', 'id')->where(function ($query) use ($currentUser, $submissionRole) {
                    $query->where('tenant_id', $currentUser?->tenant_id);

                    $linkedEmployeeId = $this->linkedEmployeeId($currentUser);

                    if ($submissionRole === 'karyawan' && $linkedEmployeeId !== null) {
                        $query->where('id', $linkedEmployeeId);
                    }
                }),
            ],
            'waktu_mulai' => ['required', 'date'],
            'waktu_selesai' => ['required', 'date', 'after:waktu_mulai'],
            'alasan' => ['nullable', 'string'],
        ]);

        $start = \Illuminate\Support\Carbon::parse($validated['waktu_mulai']);
        $end = \Illuminate\Support\Carbon::parse($validated['waktu_selesai']);

        $duplicateExists = Lembur::query()
            ->where('tenant_id', $currentUser?->tenant_id)
            ->where('employee_id', $validated['employee_id'])
            ->whereDate('waktu_mulai', $start->toDateString())
            ->exists();

        if ($duplicateExists) {
            return back()
                ->withErrors([
                    'waktu_mulai' => 'Pengajuan lembur untuk karyawan ini pada tanggal yang sama sudah ada.',
                ])
                ->withInput();
        }

        $validated['tenant_id'] = $currentUser?->tenant_id;
        $validated['submitted_by'] = $currentUser?->id;
        $validated['durasi_jam'] = round($start->floatDiffInHours($end), 2);
        $validated['pengaju'] = $submissionRole;

        $validated['status'] = $settings?->butuh_persetujuan ? 'pending' : 'disetujui';
        if ($validated['status'] === 'disetujui') {
            $validated['approver_id'] = $currentUser?->id;
        }

        Lembur::create($validated);

        return redirect()->route('lembur.index')->with('success', 'Pengajuan lembur berhasil.');
    }

    public function approve(Request $request, Lembur $lembur): RedirectResponse
    {
        $currentUser = $request->user() ?? auth()->user();

        if ($currentUser?->tenant_id !== $lembur->tenant_id) {
            abort(403);
        }

        $lembur->update([
            'status' => 'disetujui',
            'approver_id' => $currentUser?->id,
        ]);

        return back()->with('success', 'Lembur disetujui.');
    }

    public function reject(Request $request, Lembur $lembur): RedirectResponse
    {
        $currentUser = $request->user() ?? auth()->user();

        if ($currentUser?->tenant_id !== $lembur->tenant_id) {
            abort(403);
        }

        $lembur->update([
            'status' => 'ditolak',
            'approver_id' => $currentUser?->id,
        ]);

        return back()->with('error', 'Lembur ditolak.');
    }

    public function export(Request $request)
    {
        $currentUser = $request->user() ?? auth()->user();
        $filters = $this->resolveReportFilters($request);

        $lemburs = $this->applyReportSorting($this->buildReportQuery($currentUser, $filters), $filters)
            ->get();

        return Excel::download(new LemburExport($lemburs), $this->buildReportExportFilename($filters, 'xlsx'));
    }

    public function exportPdf(Request $request)
    {
        $currentUser = $request->user() ?? auth()->user();
        $filters = $this->resolveReportFilters($request);
        $reportQuery = $this->applyReportSorting($this->buildReportQuery($currentUser, $filters), $filters);
        $reports = (clone $reportQuery)->get();
        $pdfHeading = $this->buildReportPdfHeading($filters);

        $pdf = Pdf::loadView('lembur.exports.reports-pdf', [
            'reports' => $reports,
            'filters' => $filters,
            'summary' => $this->buildReportSummary(clone $reportQuery),
            'pdfHeading' => $pdfHeading,
        ])->setPaper('a4', 'landscape');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->buildReportExportFilename($filters, 'pdf').'"',
        ]);
    }

    protected function renderListingPage(Request $request, string $mode): View
    {
        $currentUser = $request->user() ?? auth()->user();
        $listingFilters = $this->resolveListingFilters($request, $mode);

        $listingQuery = $this->buildListingQuery($currentUser, $mode, $listingFilters);

        $lemburs = (clone $listingQuery)
            ->paginate(10)
            ->withQueryString();

        $lemburs->getCollection()->transform(function ($lembur) use ($mode) {
            $ageDays = $mode === 'approval' && $lembur->waktu_mulai
                ? (int) (now()->startOfDay()->diffInDays($lembur->waktu_mulai->copy()->startOfDay(), false) * -1)
                : null;

            $lembur->approval_age_days = $ageDays;
            $lembur->approval_age_tone = $ageDays === null
                ? null
                : ($ageDays >= 7 ? 'danger' : ($ageDays >= 3 ? 'warning' : 'secondary'));

            return $lembur;
        });

        $summary = $this->buildListingSummary($currentUser, $mode, $listingFilters);

        return view('lembur.index', [
            'lemburs' => $lemburs,
            'pageMode' => $mode,
            'listingFilters' => $listingFilters,
            'summary' => $summary,
            'reportSnapshot' => $this->buildListingReportSnapshot($currentUser, $mode),
            'reportSnapshotLink' => $this->buildListingReportSnapshotLink($mode),
            'approvalBacklogLink' => $mode === 'approval' ? route('lembur.reports', ['combined_preset' => 'pending_over_3_days']) : null,
        ]);
    }

    protected function buildListingQuery($currentUser, string $mode, array $filters = [])
    {
        $query = Lembur::with(['employee', 'approver', 'submitter'])
            ->when($currentUser?->tenant_id, fn ($builder) => $builder->where('tenant_id', $currentUser->tenant_id));

        if ($mode === 'approval') {
            return $query
                ->where('status', 'pending')
                ->when(($filters['backlog_filter'] ?? null) === 'over_7_days', fn ($builder) => $builder->whereDate('waktu_mulai', '<=', now()->subDays(7)->toDateString()))
                ->when($currentUser?->id, function ($builder) use ($currentUser) {
                    $builder->where(function ($nestedQuery) use ($currentUser) {
                        $nestedQuery
                            ->whereNull('submitted_by')
                            ->orWhere('submitted_by', '!=', $currentUser->id);
                    });
                })
                ->latest('waktu_mulai');
        }

        $linkedEmployeeId = $this->linkedEmployeeId($currentUser);

        return $query
            ->where(function ($builder) use ($currentUser, $linkedEmployeeId) {
                if ($currentUser?->isEmployee() && $linkedEmployeeId !== null) {
                    $builder->where('employee_id', $linkedEmployeeId);

                    return;
                }

                $builder->where('submitted_by', $currentUser?->id);

                if ($linkedEmployeeId !== null) {
                    $builder->orWhere(function ($nestedQuery) use ($linkedEmployeeId) {
                        $nestedQuery
                            ->whereNull('submitted_by')
                            ->where('employee_id', $linkedEmployeeId);
                    });
                }
            })
            ->latest('waktu_mulai');
    }

    protected function resolveListingFilters(Request $request, string $mode): array
    {
        if ($mode !== 'approval') {
            return [];
        }

        $validated = $request->validate([
            'backlog_filter' => ['nullable', Rule::in(['over_7_days'])],
        ]);

        return [
            'backlog_filter' => $validated['backlog_filter'] ?? null,
        ];
    }

    protected function resolveReportFilters(Request $request): array
    {
        return $this->resolveReportFiltersFromInput($request->all());
    }

    protected function resolveReportFiltersFromInput(array $input): array
    {
        $validated = validator($input, [
            'search' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['nullable', Rule::in(array_keys($this->reportStatusOptions()))],
            'pengaju' => ['nullable', Rule::in(array_keys($this->reportSubmissionRoleOptions()))],
            'preset' => ['nullable', Rule::in(array_keys($this->reportQuickFilterPresets()))],
            'status_preset' => ['nullable', Rule::in(array_keys($this->reportQuickStatusPresets()))],
            'combined_preset' => ['nullable', Rule::in(array_keys($this->reportQuickCombinedPresets()))],
            'sort_by' => ['nullable', Rule::in(['waktu_mulai', 'durasi_jam', 'status', 'employee_name', 'pengaju', 'approver_name', 'alasan'])],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', Rule::in([10, 25, 50, 100])],
        ])->validate();

        $combinedPreset = $validated['combined_preset'] ?? null;
        $combinedPresetValues = $this->resolveCombinedPreset($combinedPreset);
        $preset = $validated['preset'] ?? null;
        $statusPreset = $validated['status_preset'] ?? null;
        $combinedStartDate = $validated['start_date'] ?? null;
        $combinedEndDate = $validated['end_date'] ?? null;

        if ($combinedPresetValues !== null) {
            $preset = $combinedPresetValues['preset'] ?? null;
            $statusPreset = $combinedPresetValues['status_preset'] ?? null;
            $combinedStartDate = $combinedPresetValues['start_date'] ?? $combinedStartDate;
            $combinedEndDate = $combinedPresetValues['end_date'] ?? $combinedEndDate;
        }

        [$startDate, $endDate] = $this->resolvePresetDateRange($preset);
        $resolvedStatuses = $this->resolveStatusPreset($statusPreset);

        return [
            'search' => trim((string) ($validated['search'] ?? '')),
            'start_date' => $startDate ?? $combinedStartDate,
            'end_date' => $endDate ?? $combinedEndDate,
            'status' => $validated['status'] ?? (count($resolvedStatuses) === 1 ? $resolvedStatuses[0] : null),
            'status_list' => empty($validated['status']) ? $resolvedStatuses : [],
            'pengaju' => $validated['pengaju'] ?? null,
            'preset' => $preset,
            'status_preset' => $statusPreset,
            'combined_preset' => $combinedPreset,
            'sort_by' => $validated['sort_by'] ?? 'waktu_mulai',
            'sort_order' => $validated['sort_order'] ?? 'desc',
            'per_page' => (int) ($validated['per_page'] ?? 10),
        ];
    }

    protected function buildReportQuery($currentUser, array $filters): Builder
    {
        $search = $filters['search'] ?? '';

        return Lembur::query()
            ->with(['employee', 'approver', 'submitter'])
            ->when($currentUser?->tenant_id, fn (Builder $builder) => $builder->where('tenant_id', $currentUser->tenant_id))
            ->when($search !== '', function (Builder $builder) use ($search) {
                $builder->where(function (Builder $nestedQuery) use ($search) {
                    $nestedQuery
                        ->where('alasan', 'like', '%'.$search.'%')
                        ->orWhere('pengaju', 'like', '%'.$search.'%')
                        ->orWhereHas('employee', function (Builder $employeeQuery) use ($search) {
                            $employeeQuery
                                ->where('name', 'like', '%'.$search.'%')
                                ->orWhere('employee_code', 'like', '%'.$search.'%');
                        })
                        ->orWhereHas('submitter', fn (Builder $submitterQuery) => $submitterQuery->where('name', 'like', '%'.$search.'%'))
                        ->orWhereHas('approver', fn (Builder $approverQuery) => $approverQuery->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->when($filters['start_date'] ?? null, fn (Builder $builder, string $startDate) => $builder->whereDate('waktu_mulai', '>=', $startDate))
            ->when($filters['end_date'] ?? null, fn (Builder $builder, string $endDate) => $builder->whereDate('waktu_mulai', '<=', $endDate))
            ->when($filters['status'] ?? null, fn (Builder $builder, string $status) => $builder->where('status', $status))
            ->when(! empty($filters['status_list'] ?? []), fn (Builder $builder) => $builder->whereIn('status', $filters['status_list']))
            ->when($filters['pengaju'] ?? null, fn (Builder $builder, string $pengaju) => $builder->where('pengaju', $pengaju))
            ->latest('waktu_mulai');
    }

    protected function applyReportSorting(Builder $query, array $filters): Builder
    {
        $sortBy = $filters['sort_by'] ?? 'waktu_mulai';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        $query->reorder();

        return match ($sortBy) {
            'alasan' => $query->orderBy('alasan', $sortOrder)->orderBy('waktu_mulai', 'desc'),
            'durasi_jam' => $query->orderBy('durasi_jam', $sortOrder)->orderBy('waktu_mulai', 'desc'),
            'status' => $query->orderBy('status', $sortOrder)->orderBy('waktu_mulai', 'desc'),
            'pengaju' => $query
                ->orderByRaw(
                    "case pengaju when 'atasan' then 1 when 'karyawan' then 2 else 99 end ".$sortOrder
                )
                ->orderBy('waktu_mulai', 'desc'),
            'approver_name' => $query
                ->orderBy(
                    \App\Models\User::query()
                        ->select('name')
                        ->whereColumn('users.id', 'lemburs.approver_id')
                        ->limit(1),
                    $sortOrder
                )
                ->orderBy('waktu_mulai', 'desc'),
            'employee_name' => $query
                ->orderBy(
                    Employee::query()
                        ->select('name')
                        ->whereColumn('employees.id', 'lemburs.employee_id')
                        ->limit(1),
                    $sortOrder
                )
                ->orderBy('waktu_mulai', 'desc'),
            default => $query->orderBy('waktu_mulai', $sortOrder),
        };
    }

    protected function buildReportExportFilename(array $filters, string $extension): string
    {
        $segments = collect([
            'lembur-report',
            $filters['combined_preset'] ?? null,
            $filters['preset'] ?? null,
            $filters['status_preset'] ?? null,
            $filters['status'] ?? null,
            $filters['pengaju'] ?? null,
            ($filters['sort_by'] ?? 'waktu_mulai').'-'.($filters['sort_order'] ?? 'desc'),
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null,
        ])->filter()->map(function ($segment) {
            $normalized = strtolower((string) $segment);
            $normalized = preg_replace('/[^a-z0-9]+/', '-', $normalized ?? '');

            return trim($normalized ?? '', '-');
        })->filter()->values();

        return $segments->implode('-').'.'.$extension;
    }

    protected function buildReportPdfHeading(array $filters): array
    {
        $statusOptions = $this->reportStatusOptions();
        $submissionRoleOptions = $this->reportSubmissionRoleOptions();
        $quickFilterPresets = $this->reportQuickFilterPresets();
        $quickStatusPresets = $this->reportQuickStatusPresets();
        $quickCombinedPresets = $this->reportQuickCombinedPresets();
        $sortLabels = $this->reportSortLabels();

        $context = collect([
            ! empty($filters['combined_preset']) && isset($quickCombinedPresets[$filters['combined_preset']])
                ? 'Preset Kombinasi: '.$quickCombinedPresets[$filters['combined_preset']]
                : null,
            ! empty($filters['preset']) && isset($quickFilterPresets[$filters['preset']])
                ? 'Preset: '.$quickFilterPresets[$filters['preset']]
                : null,
            ! empty($filters['status_preset']) && isset($quickStatusPresets[$filters['status_preset']])
                ? 'Preset Status: '.$quickStatusPresets[$filters['status_preset']]
                : null,
            ! empty($filters['status']) && isset($statusOptions[$filters['status']])
                ? 'Status: '.$statusOptions[$filters['status']]
                : null,
            ! empty($filters['pengaju']) && isset($submissionRoleOptions[$filters['pengaju']])
                ? 'Pengaju: '.$submissionRoleOptions[$filters['pengaju']]
                : null,
            ($filters['start_date'] ?? null) || ($filters['end_date'] ?? null)
                ? 'Periode: '.($filters['start_date'] ?: '-').' s/d '.($filters['end_date'] ?: '-')
                : null,
            ($filters['search'] ?? '') !== ''
                ? 'Pencarian: '.$filters['search']
                : null,
        ])->filter()->values();

        return [
            'title' => 'Laporan Lembur'.($context->isNotEmpty() ? ' - '.$context->implode(' | ') : ' - Semua Data'),
            'subtitle' => 'Sorting: '.($sortLabels[$filters['sort_by'] ?? 'waktu_mulai'] ?? 'Tanggal Lembur').' '.strtoupper($filters['sort_order'] ?? 'desc'),
        ];
    }

    protected function reportSortLabels(): array
    {
        return [
            'employee_name' => 'Karyawan',
            'pengaju' => 'Pengaju',
            'approver_name' => 'Approver',
            'waktu_mulai' => 'Tanggal Lembur',
            'durasi_jam' => 'Durasi',
            'status' => 'Status',
            'alasan' => 'Alasan',
        ];
    }

    protected function buildReportSummary(Builder $reportQuery): array
    {
        $totalRecords = (clone $reportQuery)->count();
        $totalHours = (float) ((clone $reportQuery)->sum('durasi_jam') ?? 0);
        $approvedCount = (clone $reportQuery)->where('status', 'disetujui')->count();
        $processedCount = $approvedCount + (clone $reportQuery)->where('status', 'ditolak')->count();
        $approvalRate = $processedCount > 0 ? round(($approvedCount / $processedCount) * 100, 1) : 0;

        return [
            [
                'label' => 'Total Pengajuan',
                'value' => $totalRecords,
                'tone' => 'dark',
            ],
            [
                'label' => 'Total Jam Lembur',
                'value' => number_format($totalHours, 2).' jam',
                'tone' => 'info',
            ],
            [
                'label' => 'Disetujui',
                'value' => $approvedCount,
                'tone' => 'success',
            ],
            [
                'label' => 'Approval Rate',
                'value' => number_format($approvalRate, 1).'%',
                'tone' => 'primary',
            ],
        ];
    }

    protected function buildReportStatusBreakdown(Builder $reportQuery): array
    {
        return collect($this->reportStatusOptions())
            ->map(fn (string $label, string $status) => [
                'status' => $status,
                'label' => $label,
                'count' => (clone $reportQuery)->where('status', $status)->count(),
                'badge' => match ($status) {
                    'disetujui' => 'bg-gradient-success',
                    'ditolak' => 'bg-gradient-danger',
                    default => 'bg-gradient-warning text-dark',
                },
            ])
            ->values()
            ->all();
    }

    protected function buildApprovalInsights(Builder $reportQuery): array
    {
        $pendingQuery = (clone $reportQuery)->where('status', 'pending');
        $approvedQuery = (clone $reportQuery)->where('status', 'disetujui');

        return [
            [
                'label' => 'Menunggu Approval',
                'value' => $pendingQuery->count(),
                'helper' => number_format((float) ($pendingQuery->sum('durasi_jam') ?? 0), 2).' jam masih tertahan',
                'tone' => 'warning',
            ],
            [
                'label' => 'Rata-rata Durasi',
                'value' => number_format((float) ((clone $reportQuery)->avg('durasi_jam') ?? 0), 2).' jam',
                'helper' => 'Rata-rata per pengajuan lembur',
                'tone' => 'info',
            ],
            [
                'label' => 'Disetujui Bulan Ini',
                'value' => (clone $approvedQuery)->whereMonth('waktu_mulai', now()->month)->whereYear('waktu_mulai', now()->year)->count(),
                'helper' => 'Approval pada bulan berjalan',
                'tone' => 'success',
            ],
        ];
    }

    protected function buildTopEmployees(Builder $reportQuery): Collection
    {
        return (clone $reportQuery)
            ->reorder()
            ->selectRaw('employee_id, COUNT(*) as total_entries, COALESCE(SUM(durasi_jam), 0) as total_hours')
            ->groupBy('employee_id')
            ->orderByDesc('total_hours')
            ->limit(5)
            ->get()
            ->load('employee:id,name,employee_code');
    }

    protected function buildMonthlyTrendChart(Builder $reportQuery): array
    {
        $rows = (clone $reportQuery)
            ->reorder()
            ->selectRaw("DATE_FORMAT(waktu_mulai, '%Y-%m') as month_key")
            ->selectRaw('COUNT(*) as total_entries')
            ->selectRaw('COALESCE(SUM(durasi_jam), 0) as total_hours')
            ->groupBy('month_key')
            ->orderBy('month_key')
            ->get();

        $labels = $rows
            ->map(fn ($row) => \Illuminate\Support\Carbon::createFromFormat('Y-m', $row->month_key)->translatedFormat('M Y'))
            ->values()
            ->all();

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Total Jam Lembur',
                    'data' => $rows->map(fn ($row) => round((float) $row->total_hours, 2))->values()->all(),
                    'borderColor' => '#0d6efd',
                    'backgroundColor' => 'rgba(13, 110, 253, 0.12)',
                    'pointBackgroundColor' => '#0d6efd',
                    'pointBorderColor' => '#ffffff',
                    'pointRadius' => 4,
                    'pointHoverRadius' => 5,
                    'tension' => 0.35,
                    'fill' => true,
                    'yAxisID' => 'yHours',
                ],
                [
                    'label' => 'Jumlah Pengajuan',
                    'data' => $rows->map(fn ($row) => (int) $row->total_entries)->values()->all(),
                    'borderColor' => '#f53939',
                    'backgroundColor' => 'rgba(245, 57, 57, 0.12)',
                    'pointBackgroundColor' => '#f53939',
                    'pointBorderColor' => '#ffffff',
                    'pointRadius' => 4,
                    'pointHoverRadius' => 5,
                    'tension' => 0.35,
                    'fill' => false,
                    'yAxisID' => 'yEntries',
                ],
            ],
            'totals' => $rows->map(fn ($row) => round((float) $row->total_hours, 2))->values()->all(),
        ];
    }

    protected function buildStatusDistributionChart(Builder $reportQuery): array
    {
        $statusMeta = collect([
            'pending' => ['label' => 'Pending', 'color' => '#fbcf33'],
            'disetujui' => ['label' => 'Disetujui', 'color' => '#2dce89'],
            'ditolak' => ['label' => 'Ditolak', 'color' => '#ea0606'],
        ]);

        return [
            'labels' => $statusMeta->pluck('label')->values()->all(),
            'counts' => $statusMeta->keys()->map(fn ($status) => (clone $reportQuery)->where('status', $status)->count())->values()->all(),
            'backgroundColor' => $statusMeta->pluck('color')->values()->all(),
        ];
    }

    protected function buildTopEmployeesChart(Builder $reportQuery): array
    {
        $rows = $this->buildTopEmployees($reportQuery);

        return [
            'labels' => $rows->map(fn ($row) => $row->employee?->name ?? 'Tidak tersedia')->values()->all(),
            'hours' => $rows->map(fn ($row) => round((float) $row->total_hours, 2))->values()->all(),
            'entries' => $rows->map(fn ($row) => (int) $row->total_entries)->values()->all(),
            'backgroundColor' => ['#0d6efd', '#11cdef', '#2dce89', '#fbcf33', '#f53939'],
        ];
    }

    protected function buildListingReportSnapshot($currentUser, string $mode): array
    {
        if (! $currentUser?->hasMenuAccess('lembur.reports')) {
            return [];
        }

        $filters = [
            'search' => '',
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'status' => null,
            'pengaju' => null,
            'preset' => 'month_this',
            'per_page' => 10,
        ];

        $reportQuery = $this->buildReportQuery($currentUser, $filters);
        $approvalRate = $this->buildReportSummary(clone $reportQuery)[3]['value'];

        return $mode === 'approval'
            ? [
                [
                    'label' => 'Pending Bulan Ini',
                    'value' => (clone $reportQuery)->where('status', 'pending')->count(),
                    'tone' => 'warning',
                ],
                [
                    'label' => 'Jam Pending',
                    'value' => number_format((float) ((clone $reportQuery)->where('status', 'pending')->sum('durasi_jam') ?? 0), 2).' jam',
                    'tone' => 'info',
                ],
                [
                    'label' => 'Approval Rate',
                    'value' => $approvalRate,
                    'tone' => 'success',
                ],
            ]
            : [
                [
                    'label' => 'Lembur Bulan Ini',
                    'value' => (clone $reportQuery)->count(),
                    'tone' => 'primary',
                ],
                [
                    'label' => 'Total Jam',
                    'value' => number_format((float) ((clone $reportQuery)->sum('durasi_jam') ?? 0), 2).' jam',
                    'tone' => 'info',
                ],
                [
                    'label' => 'Approval Rate',
                    'value' => $approvalRate,
                    'tone' => 'success',
                ],
            ];
    }

    protected function reportQuickFilterPresets(): array
    {
        return [
            'today' => 'Hari Ini',
            'last_7_days' => '7 Hari Terakhir',
            'last_30_days' => '30 Hari Terakhir',
            'month_this' => 'Bulan Ini',
            'quarter_this' => 'Kuartal Ini',
        ];
    }

    protected function reportQuickStatusPresets(): array
    {
        return [
            'pending_only' => 'Pending Saja',
            'approved_only' => 'Disetujui Saja',
            'processed_only' => 'Sudah Diproses',
        ];
    }

    protected function reportQuickCombinedPresets(): array
    {
        return [
            'pending_last_7_days' => 'Pending 7 Hari Terakhir',
            'approved_month_this' => 'Approved Bulan Ini',
            'pending_over_3_days' => 'Pending > 3 Hari',
            'pending_over_7_days' => 'Pending > 7 Hari',
        ];
    }

    protected function reportQuickExportPresets(): array
    {
        return [
            [
                'label' => 'Export Pending 7 Hari',
                'helper' => 'Snapshot lembur yang masih menunggu approval dalam 7 hari terakhir.',
                'tone' => 'warning',
                'query' => [
                    'combined_preset' => 'pending_last_7_days',
                    'sort_by' => 'waktu_mulai',
                    'sort_order' => 'desc',
                ],
            ],
            [
                'label' => 'Export Backlog > 3 Hari',
                'helper' => 'Antrian lembur pending yang sudah menumpuk lebih dari 3 hari untuk tindak lanjut approval.',
                'tone' => 'danger',
                'query' => [
                    'combined_preset' => 'pending_over_3_days',
                    'sort_by' => 'waktu_mulai',
                    'sort_order' => 'asc',
                ],
            ],
            [
                'label' => 'Export Backlog > 7 Hari',
                'helper' => 'Daftar eskalasi kritis untuk pengajuan pending yang tertahan lebih dari 7 hari.',
                'tone' => 'danger',
                'query' => [
                    'combined_preset' => 'pending_over_7_days',
                    'sort_by' => 'waktu_mulai',
                    'sort_order' => 'asc',
                ],
            ],
            [
                'label' => 'Export Approved Bulan Ini',
                'helper' => 'Laporan approval lembur yang sudah disetujui pada bulan berjalan.',
                'tone' => 'success',
                'query' => [
                    'combined_preset' => 'approved_month_this',
                    'sort_by' => 'waktu_mulai',
                    'sort_order' => 'desc',
                ],
            ],
        ];
    }

    protected function buildQuickExportPresets($currentUser): array
    {
        return collect($this->reportQuickExportPresets())
            ->map(function (array $preset) use ($currentUser) {
                $filters = $this->resolveReportFiltersFromInput($preset['query']);
                $reportQuery = $this->buildReportQuery($currentUser, $filters);
                $oldestEntryAt = (clone $reportQuery)->min('waktu_mulai');
                $oldestEntryDays = $oldestEntryAt
                    ? (int) (now()->startOfDay()->diffInDays(\Illuminate\Support\Carbon::parse($oldestEntryAt)->startOfDay(), false) * -1)
                    : null;

                $preset['count'] = (clone $reportQuery)->count();
                $preset['total_hours'] = number_format((float) ((clone $reportQuery)->sum('durasi_jam') ?? 0), 2);
                $preset['empty'] = $preset['count'] === 0;
                $preset['age_days'] = $oldestEntryDays;
                $preset['priority_badge'] = $this->resolveQuickExportPriorityBadge($preset['count'], $oldestEntryDays, $preset['tone']);

                return $preset;
            })
            ->all();
    }

    protected function resolveQuickExportPriorityBadge(int $count, ?int $oldestEntryDays, string $defaultTone): array
    {
        if ($count === 0) {
            return [
                'tone' => 'secondary',
                'label' => 'Kosong',
            ];
        }

        if ($oldestEntryDays !== null && $oldestEntryDays >= 7) {
            return [
                'tone' => 'danger',
                'label' => 'Prioritas Tinggi',
            ];
        }

        if ($oldestEntryDays !== null && $oldestEntryDays >= 3) {
            return [
                'tone' => 'warning',
                'label' => 'Perlu Tindak Lanjut',
            ];
        }

        return [
            'tone' => $defaultTone,
            'label' => 'Siap Export',
        ];
    }

    protected function resolvePresetDateRange(?string $preset): array
    {
        return match ($preset) {
            'today' => [now()->toDateString(), now()->toDateString()],
            'last_7_days' => [now()->subDays(6)->toDateString(), now()->toDateString()],
            'last_30_days' => [now()->subDays(29)->toDateString(), now()->toDateString()],
            'month_this' => [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()],
            'quarter_this' => [now()->startOfQuarter()->toDateString(), now()->endOfQuarter()->toDateString()],
            default => [null, null],
        };
    }

    protected function resolveStatusPreset(?string $statusPreset): array
    {
        return match ($statusPreset) {
            'pending_only' => ['pending'],
            'approved_only' => ['disetujui'],
            'processed_only' => ['disetujui', 'ditolak'],
            default => [],
        };
    }

    protected function resolveCombinedPreset(?string $combinedPreset): ?array
    {
        return match ($combinedPreset) {
            'pending_last_7_days' => [
                'preset' => 'last_7_days',
                'status_preset' => 'pending_only',
            ],
            'pending_over_3_days' => [
                'status_preset' => 'pending_only',
                'end_date' => now()->subDays(3)->toDateString(),
            ],
            'pending_over_7_days' => [
                'status_preset' => 'pending_only',
                'end_date' => now()->subDays(7)->toDateString(),
            ],
            'approved_month_this' => [
                'preset' => 'month_this',
                'status_preset' => 'approved_only',
            ],
            default => null,
        };
    }

    protected function buildListingReportSnapshotLink(string $mode): string
    {
        $filters = ['preset' => 'month_this'];

        if ($mode === 'approval') {
            $filters['status'] = 'pending';
        }

        return route('lembur.reports', $filters);
    }

    protected function reportStatusOptions(): array
    {
        return [
            'pending' => 'Pending',
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak',
        ];
    }

    protected function reportSubmissionRoleOptions(): array
    {
        return [
            'karyawan' => 'Karyawan',
            'atasan' => 'Atasan',
        ];
    }

    protected function submissionEmployees($currentUser, string $submissionRole): Collection
    {
        $query = Employee::query()
            ->when($currentUser?->tenant_id, fn ($builder) => $builder->where('tenant_id', $currentUser->tenant_id));

        $linkedEmployeeId = $this->linkedEmployeeId($currentUser);

        if ($submissionRole === 'karyawan') {
            if ($linkedEmployeeId === null) {
                return collect();
            }

            $query->whereKey($linkedEmployeeId);
        }

        return $query->orderBy('name')->get(['id', 'name']);
    }

    protected function buildListingSummary($currentUser, string $mode, array $filters = []): array
    {
        $summaryQuery = $this->buildListingQuery($currentUser, $mode, $filters);

        if ($mode === 'approval') {
            return [
                'primary' => [
                    'label' => 'Pending Approval',
                    'value' => (clone $summaryQuery)->count(),
                    'tone' => 'warning',
                ],
                'secondary' => [
                    'label' => 'Karyawan Menunggu',
                    'value' => (clone $summaryQuery)->distinct('employee_id')->count('employee_id'),
                    'tone' => 'info',
                ],
                'tertiary' => [
                    'label' => 'Pengajuan Hari Ini',
                    'value' => (clone $summaryQuery)->whereDate('waktu_mulai', now()->toDateString())->count(),
                    'tone' => 'dark',
                ],
                'quaternary' => [
                    'label' => 'Backlog Kritis > 7 Hari',
                    'value' => (clone $this->buildListingQuery($currentUser, $mode))->whereDate('waktu_mulai', '<=', now()->subDays(7)->toDateString())->count(),
                    'tone' => 'danger',
                ],
            ];
        }

        return [
            'primary' => [
                'label' => 'Total Pengajuan',
                'value' => (clone $summaryQuery)->count(),
                'tone' => 'primary',
            ],
            'secondary' => [
                'label' => 'Masih Pending',
                'value' => (clone $summaryQuery)->where('status', 'pending')->count(),
                'tone' => 'warning',
            ],
            'tertiary' => [
                'label' => 'Sudah Disetujui',
                'value' => (clone $summaryQuery)->where('status', 'disetujui')->count(),
                'tone' => 'success',
            ],
        ];
    }

    protected function resolveSettings($currentUser): LemburSetting
    {
        return LemburSetting::query()
            ->where('tenant_id', $currentUser?->tenant_id)
            ->first()
            ?? new LemburSetting([
                'role_pengaju' => 'karyawan',
                'butuh_persetujuan' => true,
                'tipe_tarif' => 'per_jam',
            ]);
    }

    protected function resolveSubmissionRole(LemburSetting $settings): string
    {
        return $settings->role_pengaju === 'atasan' ? 'atasan' : 'karyawan';
    }

    protected function submissionAccessIssue($currentUser, string $submissionRole, Collection $employees): ?string
    {
        if ($submissionRole === 'atasan' && ! ($currentUser?->isManager() || $currentUser?->isSupervisor())) {
            return 'Tenant ini mengharuskan pengajuan lembur dibuat oleh atasan atau supervisor.';
        }

        if ($submissionRole === 'karyawan' && ! $currentUser?->isEmployee()) {
            return 'Tenant ini mengharuskan pengajuan lembur dibuat langsung oleh karyawan terkait.';
        }

        if ($submissionRole === 'karyawan' && $employees->isEmpty()) {
            return 'Akun Anda belum terhubung ke data karyawan, sehingga belum bisa mengajukan lembur.';
        }

        return null;
    }

    protected function linkedEmployeeId($currentUser): ?int
    {
        $employeeId = $currentUser?->employee_id ?? $currentUser?->assignedEmployee?->id;

        return $employeeId ? (int) $employeeId : null;
    }
}
