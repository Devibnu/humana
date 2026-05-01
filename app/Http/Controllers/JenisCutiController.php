<?php

namespace App\Http\Controllers;

use App\Models\LeaveType;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class JenisCutiController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:leaves.manage');
    }

    public function index(Request $request)
    {
        $currentUser = $request->user() ?? auth()->user();
        [$search, $tenantId, $selectedFlow] = $this->resolveIndexFilters($request, $currentUser);

        $typesQuery = LeaveType::query()
            ->with('tenant')
            ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
            ->when($selectedFlow, fn ($query) => $query->where('alur_persetujuan', $selectedFlow))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhereHas('tenant', fn ($tenantQuery) => $tenantQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderBy('name')
            ->orderBy('tenant_id');

        $types = (clone $typesQuery)
            ->paginate(10)
            ->withQueryString();

        $summaryQuery = clone $typesQuery;
        $summary = [
            'total' => (clone $summaryQuery)->count(),
            'attachments_required' => (clone $summaryQuery)->where('wajib_lampiran', true)->count(),
            'approval_required' => (clone $summaryQuery)->where('wajib_persetujuan', true)->count(),
            'auto_approved' => (clone $summaryQuery)->where('alur_persetujuan', 'auto')->count(),
        ];

        $tenants = $currentUser->isAdminHr()
            ? Tenant::orderBy('name')->get()
            : Tenant::whereKey($tenantId)->get();

        $selectedTenantName = $tenantId !== null
            ? optional($tenants->firstWhere('id', $tenantId))->name
            : null;

        return view('jenis_cuti.index', [
            'currentUser' => $currentUser,
            'type' => new LeaveType(),
            'types' => $types,
            'summary' => $summary,
            'tenants' => $tenants,
            'approvalFlows' => $this->approvalFlows(),
            'search' => $search,
            'selectedTenantId' => $tenantId,
            'selectedTenantName' => $selectedTenantName,
            'selectedFlow' => $selectedFlow,
        ]);
    }

    public function create(Request $request)
    {
        $currentUser = $request->user() ?? auth()->user();
        $tenantId = $this->resolveTenantId($request, $currentUser);

        return view('jenis_cuti.create', [
            'type' => new LeaveType(),
            'currentUser' => $currentUser,
            'tenants' => $currentUser->isAdminHr()
                ? Tenant::orderBy('name')->get()
                : Tenant::whereKey($tenantId)->get(),
            'selectedTenantId' => $tenantId,
        ]);
    }

    public function store(Request $request)
    {
        $currentUser = $request->user() ?? auth()->user();
        $tenantId = $this->resolveTenantId($request, $currentUser);

        $data = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'name' => ['required', 'string', 'max:100', Rule::unique('leave_types', 'name')->where(fn ($query) => $query->where('tenant_id', $tenantId))],
            'alur_persetujuan' => ['required', Rule::in(['single', 'multi', 'auto'])],
        ]);

        $data['tenant_id'] = $tenantId;
        $data['is_paid'] = $request->boolean('is_paid');
        $data['wajib_lampiran'] = $request->boolean('wajib_lampiran');
        $data['wajib_persetujuan'] = $request->boolean('wajib_persetujuan');

        LeaveType::create($data);

        return redirect()->route('jenis-cuti.index')->with('success', 'Jenis cuti berhasil ditambahkan.');
    }

    public function edit(Request $request, LeaveType $jenis_cuti)
    {
        $currentUser = $request->user() ?? auth()->user();

        if (! $currentUser->isAdminHr() && $currentUser->tenant_id !== $jenis_cuti->tenant_id) {
            abort(403);
        }

        return view('jenis_cuti.edit', [
            'type' => $jenis_cuti,
            'currentUser' => $currentUser,
            'tenants' => $currentUser->isAdminHr()
                ? Tenant::orderBy('name')->get()
                : Tenant::whereKey($currentUser->tenant_id)->get(),
            'selectedTenantId' => $jenis_cuti->tenant_id,
        ]);
    }

    public function update(Request $request, LeaveType $jenis_cuti)
    {
        $currentUser = $request->user() ?? auth()->user();

        if (! $currentUser->isAdminHr() && $currentUser->tenant_id !== $jenis_cuti->tenant_id) {
            abort(403);
        }

        $tenantId = $currentUser->isAdminHr() ? (int) $request->integer('tenant_id') : (int) $currentUser->tenant_id;

        $data = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'name' => ['required', 'string', 'max:100', Rule::unique('leave_types', 'name')->ignore($jenis_cuti->id)->where(fn ($query) => $query->where('tenant_id', $tenantId))],
            'alur_persetujuan' => ['required', Rule::in(['single', 'multi', 'auto'])],
        ]);

        $data['tenant_id'] = $tenantId;
        $data['is_paid'] = $request->boolean('is_paid');
        $data['wajib_lampiran'] = $request->boolean('wajib_lampiran');
        $data['wajib_persetujuan'] = $request->boolean('wajib_persetujuan');

        $jenis_cuti->update($data);

        return redirect()->route('jenis-cuti.index')->with('success', 'Jenis cuti berhasil diperbarui.');
    }

    protected function resolveTenantId(Request $request, User $currentUser): ?int
    {
        if (! $currentUser->isAdminHr()) {
            return $currentUser->tenant_id;
        }

        return (int) ($request->integer('tenant_id') ?: $currentUser->tenant_id);
    }

    protected function resolveIndexFilters(Request $request, User $currentUser): array
    {
        $search = trim((string) $request->string('search'));
        $tenantId = $currentUser->isAdminHr()
            ? ($request->filled('tenant_id') ? (int) $request->integer('tenant_id') : null)
            : $this->resolveTenantId($request, $currentUser);
        $selectedFlow = $request->string('alur_persetujuan')->value();

        if ($selectedFlow === '') {
            $selectedFlow = null;
        }

        if ($selectedFlow !== null && ! $this->approvalFlows()->has($selectedFlow)) {
            $selectedFlow = null;
        }

        return [$search, $tenantId, $selectedFlow];
    }

    protected function approvalFlows(): Collection
    {
        return collect([
            'single' => 'Single Approval',
            'multi' => 'Multi Approval',
            'auto' => 'Otomatis Disetujui',
        ]);
    }
}
