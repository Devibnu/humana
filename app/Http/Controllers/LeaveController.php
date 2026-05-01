<?php

namespace App\Http\Controllers;

use App\Exports\LeaveEmployeeExport;
use App\Exports\LeavesCsvExport;
use App\Exports\LeavesExport;
use App\Exports\LeavesWorkbookExport;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

class LeaveController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:leaves.create')->only(['create', 'store']);
        $this->middleware('permission:leaves.manage')->only(['export']);
        $this->middleware('permission:leaves.destroy')->only('destroy');
    }

    public function index(Request $request)
    {
        return $this->dashboard($request);
    }

    public function dashboard(Request $request)
    {
        $currentUser = $request->user() ?? auth()->user();
        [$tenantId, $employeeId, $status, $startDate, $endDate, $selectedMonth, $selectedYear] = $this->resolveIndexFilters($request, $currentUser);

        $leaveScopeQuery = $this->buildLeaveScopeQuery($currentUser, $tenantId);

        if ($employeeId) {
            $leaveScopeQuery->where('employee_id', $employeeId);
        }

        $this->applyOverlapDateRangeFilter($leaveScopeQuery, $startDate, $endDate);

        $summaryLeavesQuery = clone $leaveScopeQuery;
        $this->applyMonthYearFilter($summaryLeavesQuery, $selectedMonth, $selectedYear);

        $summaryLeaves = $summaryLeavesQuery->get();
        $summary = $this->buildSummary($summaryLeaves);
        $monthlySummary = $this->buildPeriodicSummary($summaryLeaves, 'month');
        $annualSummary = $this->buildPeriodicSummary($summaryLeaves, 'year');

        $leaves = (clone $leaveScopeQuery)
            ->with(['tenant', 'employee.user', 'leaveType'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->tap(fn ($query) => $this->applyMonthYearFilter($query, $selectedMonth, $selectedYear))
            ->orderByDesc('start_date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('leaves.index', [
            'leaves' => $leaves,
            'currentUser' => $currentUser,
            'tenants' => $currentUser->isAdminHr()
                ? Tenant::orderBy('name')->get()
                : Tenant::whereKey($tenantId)->get(),
            'employees' => $this->getScopedEmployees($currentUser, $tenantId),
            'statuses' => $this->statuses(),
            'selectedTenantId' => $tenantId,
            'selectedTenantName' => $tenantId ? Tenant::whereKey($tenantId)->value('name') : null,
            'selectedEmployeeId' => $employeeId,
            'selectedEmployeeName' => $employeeId ? Employee::whereKey($employeeId)->value('name') : null,
            'selectedStatus' => $status,
            'selectedStartDate' => $startDate,
            'selectedEndDate' => $endDate,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'monthOptions' => $this->monthOptions(),
            'yearOptions' => $this->getAvailableSummaryYears($currentUser, $tenantId, $employeeId, $startDate, $endDate, $selectedYear),
            'summary' => $summary,
            'monthlySummary' => $monthlySummary,
            'annualSummary' => $annualSummary,
            'monthlyTrendChart' => $this->buildMonthlyTrendChart($monthlySummary),
            'filteredSummary' => $this->buildFilteredSummaryRows($summary, $selectedMonth, $selectedYear),
            'filteredSummaryLabel' => $this->buildFilterScopeLabel($selectedMonth, $selectedYear),
            'dashboardCardSummary' => $this->buildFilteredSummaryRows($summary, $selectedMonth, $selectedYear),
            'createForm' => $this->getFormData(new Leave()),
        ]);
    }

    public function show(Request $request, Employee $employee)
    {
        $currentUser = $request->user() ?? auth()->user();
        $this->ensureUserCanAccessEmployeeSummary($employee, $currentUser);
        [$selectedMonth, $selectedYear] = $this->resolveMonthYearFilters($request);
        $yearOptions = $this->getAvailableEmployeeLeaveYears($employee, $selectedYear);
        $sparklineYear = $selectedYear ?? ($yearOptions[0] ?? (int) now()->format('Y'));

        $leaveQuery = Leave::query()
            ->with(['tenant', 'employee.user', 'leaveType'])
            ->where('employee_id', $employee->id)
            ->when($selectedMonth, fn ($query) => $query->whereMonth('start_date', $selectedMonth))
            ->when($selectedYear, fn ($query) => $query->whereYear('start_date', $selectedYear));

        $summaryLeaves = (clone $leaveQuery)->get();
        $summary = $this->buildSummary($summaryLeaves);
        $sparklineLeaves = Leave::query()
            ->where('employee_id', $employee->id)
            ->whereDate('start_date', '<=', Carbon::create($sparklineYear, 12, 31)->toDateString())
            ->whereDate('end_date', '>=', Carbon::create($sparklineYear, 1, 1)->toDateString())
            ->get(['start_date', 'end_date']);

        $leaves = $leaveQuery
            ->orderByDesc('start_date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('leaves.show', [
            'employee' => $employee->load(['tenant', 'user', 'position', 'department']),
            'leaves' => $leaves,
            'summary' => $summary,
            'currentUser' => $currentUser,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'monthOptions' => $this->monthOptions(),
            'yearOptions' => $yearOptions,
            'filteredSummaryLabel' => $this->buildFilterScopeLabel($selectedMonth, $selectedYear),
            'employeeCardSummary' => $this->buildFilteredSummaryRows($summary, $selectedMonth, $selectedYear),
            'employeeMonthlySparkline' => $this->buildEmployeeMonthlySparkline($sparklineLeaves, $sparklineYear),
        ]);
    }

    public function export(Request $request)
    {
        $currentUser = $request->user() ?? auth()->user();
        [$tenantId, $employeeId, $status, $startDate, $endDate, $selectedMonth, $selectedYear] = $this->resolveIndexFilters($request, $currentUser);
        $format = $this->resolveExportFormat($request);
        $tenantName = $tenantId ? Tenant::whereKey($tenantId)->value('name') : null;
        $employeeName = $employeeId ? Employee::whereKey($employeeId)->value('name') : null;
        $exportLeaves = $this->getLeavesForExport($currentUser, $tenantId, $employeeId, $status, $startDate, $endDate, $selectedMonth, $selectedYear);
        $summaryLeaves = $this->getSummaryLeavesForScope($currentUser, $tenantId, $employeeId, $startDate, $endDate, $selectedMonth, $selectedYear);
        $summary = $this->buildSummary($summaryLeaves);
        $monthlySummary = $this->buildPeriodicSummary($summaryLeaves, 'month');
        $annualSummary = $this->buildPeriodicSummary($summaryLeaves, 'year');
        $filteredSummary = $this->buildFilteredSummaryRows($summary, $selectedMonth, $selectedYear);
        $exportFilters = [
            'tenant_name' => $tenantName,
            'status' => $status,
            'summary' => $summary,
            'monthly_summary' => $monthlySummary,
            'annual_summary' => $annualSummary,
            'filtered_summary' => $filteredSummary,
            'filtered_summary_label' => $this->buildFilterScopeLabel($selectedMonth, $selectedYear),
            'month' => $selectedMonth,
            'year' => $selectedYear,
            'row_export_class' => LeavesExport::class,
        ];
        $filename = $this->buildExportFilename('leaves-export', $format, [
            'tenant' => $tenantName,
            'employee' => $employeeName,
            'status' => $status,
            'start' => $startDate,
            'end' => $endDate,
            'month' => $selectedMonth ? str_pad((string) $selectedMonth, 2, '0', STR_PAD_LEFT) : null,
            'year' => $selectedYear,
        ]);

        $export = $format === 'xlsx'
            ? new LeavesWorkbookExport($exportLeaves, $exportFilters)
            : new LeavesCsvExport($exportLeaves, $exportFilters);

        return Excel::download(
            $export,
            $filename,
            $format === 'xlsx' ? ExcelFormat::XLSX : ExcelFormat::CSV
        );
    }

    public function exportEmployee(Request $request, Employee $employee)
    {
        $currentUser = $request->user() ?? auth()->user();
        $this->ensureUserCanAccessEmployeeSummary($employee, $currentUser);
        $format = $this->resolveExportFormat($request);
        [$selectedMonth, $selectedYear] = $this->resolveMonthYearFilters($request);
        $exportLeaves = Leave::with(['tenant', 'employee.user'])
            ->with('leaveType')
            ->where('employee_id', $employee->id)
            ->when($selectedMonth, fn ($query) => $query->whereMonth('start_date', $selectedMonth))
            ->when($selectedYear, fn ($query) => $query->whereYear('start_date', $selectedYear))
            ->orderBy('start_date')
            ->get();
        $summary = $this->buildSummary($exportLeaves);
        $monthlySummary = $this->buildPeriodicSummary($exportLeaves, 'month');
        $annualSummary = $this->buildPeriodicSummary($exportLeaves, 'year');
        $exportFilters = [
            'tenant_name' => $employee->tenant?->name,
            'summary' => $summary,
            'monthly_summary' => $monthlySummary,
                'monthlyTrendChart' => $this->buildMonthlyTrendChart($monthlySummary),
                'filteredSummary' => $this->buildFilteredSummaryRows($summary, $selectedMonth, $selectedYear),
                'filteredSummaryLabel' => $this->buildFilterScopeLabel($selectedMonth, $selectedYear),
                'dashboardCardSummary' => $this->buildFilteredSummaryRows($summary, $selectedMonth, $selectedYear),
            'filtered_summary' => $this->buildFilteredSummaryRows($summary, $selectedMonth, $selectedYear),
            'filtered_summary_label' => $this->buildFilterScopeLabel($selectedMonth, $selectedYear),
            'month' => $selectedMonth,
            'year' => $selectedYear,
            'row_export_class' => LeaveEmployeeExport::class,
        ];
        $filename = $this->buildExportFilename('employee-leaves-export', $format, [
            'employee' => $employee->name,
            'tenant' => $employee->tenant?->name,
            'month' => $selectedMonth ? str_pad((string) $selectedMonth, 2, '0', STR_PAD_LEFT) : null,
            'year' => $selectedYear,
        ]);

        $export = $format === 'xlsx'
            ? new LeavesWorkbookExport($exportLeaves, $exportFilters)
            : new LeavesCsvExport($exportLeaves, $exportFilters);

        return Excel::download(
            $export,
            $filename,
            $format === 'xlsx' ? ExcelFormat::XLSX : ExcelFormat::CSV
        );
    }

    public function create()
    {
        $currentUser = auth()->user();

        if ($currentUser?->isEmployee()) {
            $this->resolveAuthenticatedEmployee($currentUser);
        }

        return view('leaves.create', $this->getFormData(new Leave()));
    }

    public function store(Request $request)
    {
        $currentUser = $request->user() ?? auth()->user();
        $tenantId = $this->resolveTenantIdForForm($currentUser, $request);
        $employeeRule = Rule::exists('employees', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId));

        if ($currentUser->isEmployee()) {
            $employee = $this->resolveAuthenticatedEmployee($currentUser);
            $request->merge([
                'tenant_id' => $employee->tenant_id,
                'employee_id' => $employee->id,
            ]);
            $tenantId = (int) $employee->tenant_id;
            $employeeRule = Rule::exists('employees', 'id')->where(fn ($query) => $query->where('id', $employee->id));
        }

        $data = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'employee_id' => ['required', $employeeRule],
            'leave_type_id' => [
                'required',
                Rule::exists('leave_types', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string'],
            'attachment' => ['nullable', 'file', 'max:2048', 'mimes:pdf,jpg,jpeg,png'],
        ]);

        $leaveType = LeaveType::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($data['leave_type_id'])
            ->firstOrFail();

        if ($leaveType->wajib_lampiran && ! $request->hasFile('attachment')) {
            throw ValidationException::withMessages([
                'attachment' => 'Lampiran bukti wajib diunggah untuk jenis cuti ini.',
            ]);
        }

        $data['tenant_id'] = $tenantId;

        if ($request->hasFile('attachment')) {
            $data['attachment_path'] = $request->file('attachment')->store('leave-attachments', 'public');
        }

        $data = array_merge($data, $this->buildInitialApprovalState($leaveType));

        unset($data['attachment']);

        Leave::create($data);

        session()->flash('success', 'Permintaan cuti berhasil ditambahkan');

        return redirect()->route('leaves.index');
    }

    public function edit(Leave $leaf)
    {
        $this->ensureUserCanEditLeave($leaf);

        return view('leaves.edit', $this->getFormData($leaf));
    }

    public function update(Request $request, Leave $leaf)
    {
        $currentUser = $request->user() ?? auth()->user();
        $this->ensureUserCanEditLeave($leaf);

        if ($currentUser->isSupervisor() || $currentUser->isManager()) {
            $data = $request->validate([
                'status' => ['required', Rule::in(array_keys($this->statuses()))],
            ]);

            $leaf->update($this->resolveApprovalTransition($leaf, $currentUser, $data['status']));

            return redirect()->route('leaves.index')->with('success', 'Leave status updated successfully.');
        }

        $tenantId = $this->resolveTenantIdForForm($currentUser, $request, $leaf);

        $data = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'employee_id' => [
                'required',
                Rule::exists('employees', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'leave_type_id' => [
                'required',
                Rule::exists('leave_types', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string'],
            'status' => ['required', Rule::in(array_keys($this->statuses()))],
            'attachment' => ['nullable', 'file', 'max:2048', 'mimes:pdf,jpg,jpeg,png'],
        ]);

        $leaveType = LeaveType::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($data['leave_type_id'])
            ->firstOrFail();

        if ($leaveType->wajib_lampiran && ! $request->hasFile('attachment') && ! $leaf->attachment_path) {
            throw ValidationException::withMessages([
                'attachment' => 'Lampiran bukti wajib diunggah untuk jenis cuti ini.',
            ]);
        }

        $data['tenant_id'] = $tenantId;

        if ($request->hasFile('attachment')) {
            $data['attachment_path'] = $request->file('attachment')->store('leave-attachments', 'public');
        }

        $data = array_merge($data, $this->resolveApprovalTransition($leaf, $currentUser, $data['status']));

        unset($data['attachment']);

        $leaf->update($data);

        return redirect()->route('leaves.index')->with('success', 'Leave request updated successfully.');
    }

    public function destroy(Leave $leaf)
    {
        $leaf->delete();

        return redirect()->route('leaves.index')->with('success', 'Leave request deleted successfully.');
    }

    protected function tenantLeaveTypes(?int $tenantId)
    {
        if (! $tenantId) {
            return collect();
        }

        $types = LeaveType::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get();

        if ($types->isNotEmpty()) {
            return $types;
        }

        foreach (LeaveType::defaults() as $default) {
            LeaveType::query()->firstOrCreate(
                ['tenant_id' => $tenantId, 'name' => $default['name']],
                [
                    'is_paid' => $default['is_paid'],
                    'wajib_lampiran' => $default['wajib_lampiran'] ?? false,
                    'wajib_persetujuan' => $default['wajib_persetujuan'] ?? true,
                    'alur_persetujuan' => $default['alur_persetujuan'] ?? 'single',
                ]
            );
        }

        return LeaveType::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get();
    }

    protected function statuses(): array
    {
        return [
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
        ];
    }

    protected function buildInitialApprovalState(LeaveType $leaveType): array
    {
        if (! $leaveType->wajib_persetujuan || $leaveType->alur_persetujuan === 'auto') {
            return [
                'status' => 'approved',
                'approval_stage' => null,
                'current_approval_role' => null,
            ];
        }

        if ($leaveType->alur_persetujuan === 'multi') {
            return [
                'status' => 'pending',
                'approval_stage' => 'supervisor',
                'current_approval_role' => 'supervisor',
            ];
        }

        return [
            'status' => 'pending',
            'approval_stage' => 'manager',
            'current_approval_role' => 'manager',
        ];
    }

    protected function resolveApprovalTransition(Leave $leave, User $currentUser, string $requestedStatus): array
    {
        $requestedStatus = strtolower($requestedStatus);
        $leaveType = $leave->relationLoaded('leaveType') ? $leave->leaveType : $leave->leaveType()->first();

        if (! $leaveType) {
            return [
                'status' => $requestedStatus,
                'approval_stage' => $leave->approval_stage,
                'current_approval_role' => $leave->current_approval_role,
            ];
        }

        if ($requestedStatus === 'rejected') {
            if ($leave->status === 'pending' && ! $this->canApproveStage($currentUser, $leave->current_approval_role)) {
                abort(403);
            }

            return [
                'status' => 'rejected',
                'approval_stage' => null,
                'current_approval_role' => null,
            ];
        }

        if ($requestedStatus !== 'approved') {
            return [
                'status' => $requestedStatus,
                'approval_stage' => $leave->approval_stage,
                'current_approval_role' => $leave->current_approval_role,
            ];
        }

        if (! $leaveType->wajib_persetujuan || $leaveType->alur_persetujuan === 'auto') {
            return [
                'status' => 'approved',
                'approval_stage' => null,
                'current_approval_role' => null,
            ];
        }

        if (! $this->canApproveStage($currentUser, $leave->current_approval_role)) {
            abort(403);
        }

        if ($leaveType->alur_persetujuan === 'single') {
            return [
                'status' => 'approved',
                'approval_stage' => null,
                'current_approval_role' => null,
            ];
        }

        $currentStage = $leave->current_approval_role ?: 'supervisor';

        if ($currentStage === 'supervisor') {
            return [
                'status' => 'pending',
                'approval_stage' => 'manager',
                'current_approval_role' => 'manager',
            ];
        }

        if ($currentStage === 'manager') {
            return [
                'status' => 'pending',
                'approval_stage' => 'hr',
                'current_approval_role' => 'hr',
            ];
        }

        return [
            'status' => 'approved',
            'approval_stage' => null,
            'current_approval_role' => null,
        ];
    }

    protected function canApproveStage(User $currentUser, ?string $stage): bool
    {
        if ($stage === null) {
            return $currentUser->hasMenuAccess('leaves.approval.supervisor')
                || $currentUser->hasMenuAccess('leaves.approval.manager')
                || $currentUser->hasMenuAccess('leaves.approval.hr');
        }

        return match ($stage) {
            'supervisor' => $currentUser->hasMenuAccess('leaves.approval.supervisor'),
            'manager' => $currentUser->hasMenuAccess('leaves.approval.manager'),
            'hr' => $currentUser->hasMenuAccess('leaves.approval.hr'),
            default => false,
        };
    }

    protected function resolveIndexFilters(Request $request, User $currentUser): array
    {
        $tenantId = $request->integer('tenant_id');
        $employeeId = $request->integer('employee_id');
        $status = $request->string('status')->value();
        $startDate = $request->date('start_date')?->format('Y-m-d');
        $endDate = $request->date('end_date')?->format('Y-m-d');
        [$month, $year] = $this->resolveMonthYearFilters($request);

        if (! in_array($status, array_keys($this->statuses()), true)) {
            $status = null;
        }

        if ($currentUser->isSupervisor() || $currentUser->isManager() || $currentUser->isEmployee()) {
            $tenantId = $currentUser->tenant_id;
        }

        if ($currentUser->isEmployee()) {
            $employeeId = $this->resolveAuthenticatedEmployee($currentUser)->id;
        }

        return [$tenantId, $employeeId, $status, $startDate, $endDate, $month, $year];
    }

    protected function resolveMonthYearFilters(Request $request): array
    {
        return $this->normalizeMonthYearFilters($request->integer('month'), $request->integer('year'));
    }

    protected function normalizeMonthYearFilters(?int $month, ?int $year): array
    {
        if ($month < 1 || $month > 12) {
            $month = null;
        }

        if ($year < 1900 || $year > 3000) {
            $year = null;
        }

        return [$month, $year];
    }

    protected function buildLeaveScopeQuery(User $currentUser, ?int $tenantId)
    {
        return Leave::query()
            ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
            ->when($currentUser->isSupervisor(), fn ($query) => $query->where('tenant_id', $currentUser->tenant_id))
            ->when($currentUser->isEmployee(), function ($query) use ($currentUser) {
                $query->whereHas('employee', fn ($employeeQuery) => $employeeQuery->where('user_id', $currentUser->id));
            });
    }

    protected function getLeavesForExport(User $currentUser, ?int $tenantId, ?int $employeeId, ?string $status, ?string $startDate, ?string $endDate, ?int $month = null, ?int $year = null): Collection
    {
        $query = $this->buildLeaveScopeQuery($currentUser, $tenantId);

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        $this->applyOverlapDateRangeFilter($query, $startDate, $endDate);
        $this->applyMonthYearFilter($query, $month, $year);

        return $query
            ->with(['tenant', 'employee.user', 'leaveType'])
            ->when($status, fn ($builder) => $builder->where('status', $status))
            ->orderBy('start_date')
            ->get();
    }

    protected function applyOverlapDateRangeFilter($query, ?string $startDate, ?string $endDate): void
    {
        if ($startDate && $endDate) {
            $query->whereDate('start_date', '<=', $endDate)
                ->whereDate('end_date', '>=', $startDate);

            return;
        }

        if ($startDate) {
            $query->whereDate('end_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('start_date', '<=', $endDate);
        }
    }

    protected function buildSummary(Collection $leaves): array
    {
        $summary = [
            'pending' => ['requests' => 0, 'days' => 0],
            'approved' => ['requests' => 0, 'days' => 0],
            'rejected' => ['requests' => 0, 'days' => 0],
        ];

        foreach ($leaves as $leave) {
            if (! array_key_exists($leave->status, $summary)) {
                continue;
            }

            $summary[$leave->status]['requests']++;
            $summary[$leave->status]['days'] += (int) $leave->duration;
        }

        return $summary;
    }

    protected function getSummaryForScope(User $currentUser, ?int $tenantId, ?int $employeeId, ?string $startDate, ?string $endDate, ?int $month = null, ?int $year = null): array
    {
        return $this->buildSummary($this->getSummaryLeavesForScope($currentUser, $tenantId, $employeeId, $startDate, $endDate, $month, $year));
    }

    protected function getSummaryLeavesForScope(User $currentUser, ?int $tenantId, ?int $employeeId, ?string $startDate, ?string $endDate, ?int $month = null, ?int $year = null): Collection
    {
        $query = $this->buildLeaveScopeQuery($currentUser, $tenantId);

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        $this->applyOverlapDateRangeFilter($query, $startDate, $endDate);
        $this->applyMonthYearFilter($query, $month, $year);

        return $query->get();
    }

    protected function applyMonthYearFilter($query, ?int $month, ?int $year): void
    {
        if ($month) {
            $query->whereMonth('start_date', $month);
        }

        if ($year) {
            $query->whereYear('start_date', $year);
        }
    }

    protected function buildPeriodicSummary(Collection $leaves, string $period): array
    {
        $periodSummaries = [];

        foreach ($leaves as $leave) {
            if (! $leave->start_date || ! in_array($period, ['month', 'year'], true)) {
                continue;
            }

            $periodDate = $leave->start_date instanceof Carbon
                ? $leave->start_date->copy()
                : Carbon::parse($leave->start_date);
            $periodKey = $period === 'month' ? $periodDate->format('Y-m') : $periodDate->format('Y');
            $periodLabel = $period === 'month' ? $periodDate->format('M Y') : $periodDate->format('Y');

            if (! array_key_exists($periodKey, $periodSummaries)) {
                $periodSummaries[$periodKey] = [
                    'period_key' => $periodKey,
                    'period_label' => $periodLabel,
                    'statuses' => [
                        'pending' => ['requests' => 0, 'days' => 0],
                        'approved' => ['requests' => 0, 'days' => 0],
                        'rejected' => ['requests' => 0, 'days' => 0],
                    ],
                ];
            }

            if (! array_key_exists($leave->status, $periodSummaries[$periodKey]['statuses'])) {
                continue;
            }

            $periodSummaries[$periodKey]['statuses'][$leave->status]['requests']++;
            $periodSummaries[$periodKey]['statuses'][$leave->status]['days'] += (int) $leave->duration;
        }

        ksort($periodSummaries);

        $rows = [];

        foreach ($periodSummaries as $periodSummary) {
            foreach ($this->statuses() as $status => $label) {
                $rows[] = [
                    'period_key' => $periodSummary['period_key'],
                    'period_label' => $periodSummary['period_label'],
                    'status' => $status,
                    'status_label' => $label,
                    'requests' => (int) ($periodSummary['statuses'][$status]['requests'] ?? 0),
                    'days' => (int) ($periodSummary['statuses'][$status]['days'] ?? 0),
                ];
            }
        }

        return $rows;
    }

    protected function buildMonthlyTrendChart(array $monthlySummary): array
    {
        $chart = [
            'labels' => [],
            'pending_requests' => [],
            'approved_requests' => [],
            'rejected_requests' => [],
            'total_days' => [],
        ];

        $periods = [];

        foreach ($monthlySummary as $row) {
            $periodKey = $row['period_key'];

            if (! array_key_exists($periodKey, $periods)) {
                $periods[$periodKey] = [
                    'label' => $row['period_label'],
                    'pending' => 0,
                    'approved' => 0,
                    'rejected' => 0,
                    'total_days' => 0,
                ];
            }

            $periods[$periodKey][$row['status']] = (int) $row['requests'];
            $periods[$periodKey]['total_days'] += (int) $row['days'];
        }

        foreach ($periods as $period) {
            $chart['labels'][] = $period['label'];
            $chart['pending_requests'][] = $period['pending'];
            $chart['approved_requests'][] = $period['approved'];
            $chart['rejected_requests'][] = $period['rejected'];
            $chart['total_days'][] = $period['total_days'];
        }

        return $chart;
    }

    protected function buildFilteredSummaryRows(array $summary, ?int $month, ?int $year): array
    {
        $scopeLabel = $this->buildFilterScopeLabel($month, $year);
        $rows = [];

        foreach ($this->statuses() as $status => $label) {
            $rows[] = [
                'filter_scope' => $scopeLabel,
                'status' => $status,
                'status_label' => $label,
                'requests' => (int) ($summary[$status]['requests'] ?? 0),
                'days' => (int) ($summary[$status]['days'] ?? 0),
            ];
        }

        return $rows;
    }

    protected function buildFilterScopeLabel(?int $month, ?int $year): string
    {
        if ($month && $year) {
            return Carbon::createFromDate($year, $month, 1)->format('F Y');
        }

        if ($month) {
            return Carbon::createFromDate(2000, $month, 1)->format('F').' (all years)';
        }

        if ($year) {
            return (string) $year;
        }

        return 'Current Scope';
    }

    protected function monthOptions(): array
    {
        $months = [];

        for ($month = 1; $month <= 12; $month++) {
            $months[$month] = Carbon::createFromDate(2000, $month, 1)->format('M');
        }

        return $months;
    }

    protected function getAvailableSummaryYears(User $currentUser, ?int $tenantId, ?int $employeeId, ?string $startDate, ?string $endDate, ?int $selectedYear = null): array
    {
        $query = $this->buildLeaveScopeQuery($currentUser, $tenantId);

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        $this->applyOverlapDateRangeFilter($query, $startDate, $endDate);

        $years = $query->get(['start_date'])
            ->filter(fn ($leave) => $leave->start_date !== null)
            ->map(fn ($leave) => (int) ($leave->start_date instanceof Carbon ? $leave->start_date->format('Y') : Carbon::parse($leave->start_date)->format('Y')))
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        if ($selectedYear && ! in_array($selectedYear, $years, true)) {
            $years[] = $selectedYear;
            rsort($years);
        }

        if ($years === []) {
            return [(int) now()->format('Y')];
        }

        return $years;
    }

    protected function getAvailableEmployeeLeaveYears(Employee $employee, ?int $selectedYear = null): array
    {
        $years = Leave::query()
            ->where('employee_id', $employee->id)
            ->get(['start_date', 'end_date'])
            ->flatMap(function ($leave) {
                if (! $leave->start_date || ! $leave->end_date) {
                    return [];
                }

                $startYear = (int) ($leave->start_date instanceof Carbon ? $leave->start_date->format('Y') : Carbon::parse($leave->start_date)->format('Y'));
                $endYear = (int) ($leave->end_date instanceof Carbon ? $leave->end_date->format('Y') : Carbon::parse($leave->end_date)->format('Y'));

                return range($startYear, $endYear);
            })
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        if ($selectedYear && ! in_array($selectedYear, $years, true)) {
            $years[] = $selectedYear;
            rsort($years);
        }

        if ($years === []) {
            return [(int) now()->format('Y')];
        }

        return $years;
    }

    protected function buildEmployeeMonthlySparkline(Collection $leaves, int $year, string $aggregationMode = 'split_range'): array
    {
        $daysByMonth = array_fill(1, 12, 0);

        foreach ($leaves as $leave) {
            if (! $leave->start_date || ! $leave->end_date) {
                continue;
            }

            $startDate = $leave->start_date instanceof Carbon
                ? $leave->start_date->copy()
                : Carbon::parse($leave->start_date);
            $endDate = $leave->end_date instanceof Carbon
                ? $leave->end_date->copy()
                : Carbon::parse($leave->end_date);

            if ($aggregationMode === 'simple') {
                if ((int) $startDate->format('Y') !== $year) {
                    continue;
                }

                $daysByMonth[(int) $startDate->format('n')] += (int) ($leave->duration ?? 0);

                continue;
            }

            $yearStart = Carbon::create($year, 1, 1)->startOfDay();
            $yearEnd = Carbon::create($year, 12, 31)->endOfDay();
            $rangeStart = $startDate->greaterThan($yearStart) ? $startDate->copy() : $yearStart->copy();
            $rangeEnd = $endDate->lessThan($yearEnd) ? $endDate->copy() : $yearEnd->copy();

            if ($rangeStart->greaterThan($rangeEnd)) {
                continue;
            }

            $cursor = $rangeStart->copy();

            while ($cursor->lessThanOrEqualTo($rangeEnd)) {
                $daysByMonth[(int) $cursor->format('n')]++;
                $cursor->addDay();
            }
        }

        $labels = [];
        $days = [];

        foreach (range(1, 12) as $month) {
            $labels[] = Carbon::createFromDate($year, $month, 1)->format('M');
            $days[] = (int) $daysByMonth[$month];
        }

        return [
            'year' => $year,
            'labels' => $labels,
            'days' => $days,
            'has_data' => collect($days)->sum() > 0,
            'empty_state' => 'No leave days recorded for this year.',
            'tooltip_suffix' => 'days',
            'aggregation_mode' => $aggregationMode,
        ];
    }

    protected function ensureUserCanAccessEmployeeSummary(Employee $employee, ?User $currentUser): void
    {
        abort_unless($currentUser, 401);

        if ($currentUser->isAdminHr()) {
            return;
        }

        if (($currentUser->isSupervisor() || $currentUser->isManager()) && $currentUser->tenant_id === $employee->tenant_id) {
            return;
        }

        if ($currentUser->isEmployee() && $employee->user_id === $currentUser->id) {
            return;
        }

        abort(403);
    }

    protected function getScopedEmployees(?User $currentUser, ?int $tenantId)
    {
        return Employee::query()
            ->when($currentUser?->isEmployee(), fn ($query) => $query->where('user_id', $currentUser->id))
            ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
            ->orderBy('name')
            ->get();
    }

    protected function resolveAuthenticatedEmployee(User $currentUser): Employee
    {
        $employee = Employee::where('user_id', $currentUser->id)->first();

        abort_unless($employee, 403);

        return $employee;
    }

    protected function ensureUserCanEditLeave(Leave $leave): void
    {
        $currentUser = auth()->user();

        abort_unless($currentUser, 401);

        if ($currentUser->isAdminHr()) {
            return;
        }

        abort_unless((int) $currentUser->tenant_id === (int) $leave->tenant_id, 403);

        if ($currentUser->isSupervisor() && $leave->current_approval_role === 'supervisor' && $currentUser->hasMenuAccess('leaves.approval.supervisor')) {
            return;
        }

        if ($currentUser->isManager() && in_array($leave->current_approval_role, ['manager'], true) && $currentUser->hasMenuAccess('leaves.approval.manager')) {
            return;
        }

        abort(403);
    }

    protected function resolveTenantIdForForm(User $currentUser, Request $request, ?Leave $leave = null): int
    {
        if ($currentUser->isSupervisor() || $currentUser->isManager() || $currentUser->isEmployee()) {
            return (int) $currentUser->tenant_id;
        }

        return (int) ($request->integer('tenant_id') ?: $leave?->tenant_id);
    }

    protected function resolveExportFormat(Request $request): string
    {
        $format = strtolower($request->string('format')->value() ?: 'csv');

        return in_array($format, ['csv', 'xlsx'], true) ? $format : 'csv';
    }

    protected function buildExportFilename(string $base, string $format, array $filters): string
    {
        $parts = [$base, now()->format('Ymd')];

        foreach ($filters as $key => $value) {
            if ($value !== null && $value !== '') {
                $parts[] = $key.'-'.Str::slug((string) $value, '-');
            }
        }

        if (count($parts) === 2) {
            $parts[] = 'all';
        }

        return implode('_', $parts).'.'.$format;
    }

    protected function getFormData(Leave $leave): array
    {
        $currentUser = auth()->user();
        $tenantId = $currentUser?->isAdminHr()
            ? (old('tenant_id') ?: $leave->tenant_id ?: $currentUser?->tenant_id)
            : $currentUser?->tenant_id;
        $selectedTenantId = $tenantId ? (int) $tenantId : null;

        return [
            'leave' => $leave,
            'currentUser' => $currentUser,
            'tenants' => $currentUser?->isAdminHr()
                ? Tenant::orderBy('name')->get()
                : Tenant::whereKey($tenantId)->get(),
            'employees' => $this->getScopedEmployees($currentUser, $tenantId),
            'allEmployees' => Employee::query()
                ->when($currentUser?->isEmployee(), fn ($query) => $query->where('user_id', $currentUser->id))
                ->when($currentUser?->isSupervisor(), fn ($query) => $query->where('tenant_id', $currentUser->tenant_id))
                ->when($currentUser?->isManager(), fn ($query) => $query->where('tenant_id', $currentUser->tenant_id))
                ->orderBy('name')
                ->get(['id', 'tenant_id', 'name']),
            'tenantLeaveTypes' => $this->tenantLeaveTypes($selectedTenantId),
            'statuses' => $this->statuses(),
            'isTenantLocked' => ! $currentUser?->isAdminHr(),
            'isEmployeeLocked' => (bool) $currentUser?->isEmployee(),
            'scopedTenantId' => $tenantId,
            'selectedTenantId' => $selectedTenantId,
            'canEditDetails' => (bool) ($currentUser?->isAdminHr() || $currentUser?->isEmployee()),
            'canChangeStatus' => (bool) ($currentUser?->isAdminHr() || $currentUser?->isManager() || $currentUser?->isSupervisor()),
        ];
    }
}