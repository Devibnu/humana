<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\WorkSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WorkScheduleController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:work_schedules');
    }

    public function index(Request $request)
    {
        $currentUser = $request->user();
        $statuses = $this->statuses();
        $search = trim((string) $request->query('search', ''));
        $selectedTenantId = $currentUser?->isManager() ? $currentUser->tenant_id : ($request->integer('tenant_id') ?: null);
        $selectedStatus = $request->filled('status') && array_key_exists((string) $request->query('status'), $statuses)
            ? (string) $request->query('status')
            : null;

        $baseQuery = $this->buildScopeQuery($search, $selectedTenantId, $selectedStatus)
            ->with('tenant')
            ->withCount('employees');

        $workSchedules = (clone $baseQuery)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $summaryQuery = $this->buildScopeQuery($search, $selectedTenantId, $selectedStatus);
        $summary = [
            'total' => (clone $summaryQuery)->count(),
            'active' => (clone $summaryQuery)->where('status', 'active')->count(),
            'inactive' => (clone $summaryQuery)->where('status', 'inactive')->count(),
        ];

        $tenants = $this->tenantOptions();

        return view('work_schedules.index', [
            'workSchedules' => $workSchedules,
            'workSchedule' => new WorkSchedule([
                'check_in_time' => '08:00',
                'check_out_time' => '17:00',
                'late_tolerance_minutes' => 0,
                'early_leave_tolerance_minutes' => 0,
                'status' => 'active',
                'sort_order' => 0,
            ]),
            'tenants' => $tenants,
            'statuses' => $statuses,
            'summary' => $summary,
            'search' => $search,
            'selectedTenantId' => $selectedTenantId,
            'selectedTenantName' => $selectedTenantId ? optional($tenants->firstWhere('id', $selectedTenantId))->name : null,
            'selectedStatus' => $selectedStatus,
        ]);
    }

    public function create()
    {
        return view('work_schedules.create', [
            'workSchedule' => new WorkSchedule([
                'check_in_time' => '08:00',
                'check_out_time' => '17:00',
                'late_tolerance_minutes' => 0,
                'early_leave_tolerance_minutes' => 0,
                'status' => 'active',
                'sort_order' => 0,
            ]),
            'tenants' => $this->tenantOptions(),
            'statuses' => $this->statuses(),
        ]);
    }

    public function store(Request $request)
    {
        WorkSchedule::create($this->validatedData($request));

        return redirect()->route('work-schedules.index')->with('success', 'Jadwal kerja berhasil ditambahkan.');
    }

    public function edit(WorkSchedule $workSchedule)
    {
        $this->ensureManagerCanAccess($workSchedule);

        return view('work_schedules.edit', [
            'workSchedule' => $workSchedule,
            'tenants' => $this->tenantOptions(),
            'statuses' => $this->statuses(),
        ]);
    }

    public function update(Request $request, WorkSchedule $workSchedule)
    {
        $this->ensureManagerCanAccess($workSchedule);

        $workSchedule->update($this->validatedData($request, $workSchedule));

        return redirect()->route('work-schedules.index')->with('success', 'Jadwal kerja berhasil diperbarui.');
    }

    public function destroy(WorkSchedule $workSchedule)
    {
        $this->ensureManagerCanAccess($workSchedule);

        if ($workSchedule->employees()->exists()) {
            return redirect()
                ->route('work-schedules.index')
                ->withErrors(['work_schedule' => 'Jadwal kerja masih digunakan oleh data karyawan.']);
        }

        $workSchedule->delete();

        return redirect()->route('work-schedules.index')->with('success', 'Jadwal kerja berhasil dihapus.');
    }

    protected function validatedData(Request $request, ?WorkSchedule $workSchedule = null): array
    {
        $tenantId = $this->resolvedTenantId($request, $workSchedule);
        $code = Str::slug((string) ($request->input('code') ?: $request->input('name')), '_');

        $request->merge([
            'tenant_id' => $tenantId,
            'code' => $code,
            'status' => $request->input('status', 'active'),
            'sort_order' => $request->integer('sort_order'),
        ]);

        return $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('work_schedules', 'code')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->ignore($workSchedule?->id),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('work_schedules', 'name')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->ignore($workSchedule?->id),
            ],
            'check_in_time' => ['required', 'date_format:H:i'],
            'check_out_time' => ['required', 'date_format:H:i'],
            'late_tolerance_minutes' => ['required', 'integer', 'min:0', 'max:240'],
            'early_leave_tolerance_minutes' => ['required', 'integer', 'min:0', 'max:240'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(array_keys($this->statuses()))],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
    }

    protected function buildScopeQuery(string $search, ?int $tenantId, ?string $status)
    {
        $currentUser = auth()->user();

        return WorkSchedule::query()
            ->when($currentUser?->isManager(), fn ($query) => $query->where('tenant_id', $currentUser->tenant_id))
            ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('check_in_time', 'like', "%{$search}%")
                        ->orWhere('check_out_time', 'like', "%{$search}%");
                });
            });
    }

    protected function resolvedTenantId(Request $request, ?WorkSchedule $workSchedule = null): int
    {
        $currentUser = $request->user();

        if ($currentUser?->isManager()) {
            return (int) $currentUser->tenant_id;
        }

        return (int) ($request->integer('tenant_id') ?: $workSchedule?->tenant_id ?: $currentUser?->tenant_id);
    }

    protected function ensureManagerCanAccess(WorkSchedule $workSchedule): void
    {
        $currentUser = auth()->user();

        if ($currentUser?->isManager() && $currentUser->tenant_id !== $workSchedule->tenant_id) {
            abort(403);
        }
    }

    protected function tenantOptions()
    {
        $currentUser = auth()->user();

        return $currentUser?->isManager()
            ? Tenant::whereKey($currentUser->tenant_id)->get()
            : Tenant::orderBy('name')->get();
    }

    protected function statuses(): array
    {
        return [
            'active' => 'Aktif',
            'inactive' => 'Tidak Aktif',
        ];
    }
}
