<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\WorkLocation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class WorkLocationController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:work_locations');
    }

    public function index(Request $request)
    {
        $currentUser = $request->user() ?? auth()->user();

        $search = trim((string) $request->query('search', ''));
        $selectedTenantId = $request->filled('tenant_id') ? $request->integer('tenant_id') : null;

        $baseQuery = WorkLocation::query()
            ->with(['tenant'])
            ->withCount('employees')
            ->when($currentUser?->isManager(), fn ($query) => $query->where('tenant_id', $currentUser->tenant_id))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nestedQuery) use ($search) {
                    $nestedQuery
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('address', 'like', '%'.$search.'%')
                        ->orWhere('latitude', 'like', '%'.$search.'%')
                        ->orWhere('longitude', 'like', '%'.$search.'%')
                        ->orWhere('radius', 'like', '%'.$search.'%')
                        ->orWhereHas('tenant', fn ($tenantQuery) => $tenantQuery->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->when($selectedTenantId, fn ($query) => $query->where('tenant_id', $selectedTenantId));

        $workLocations = (clone $baseQuery)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $summaryQuery = clone $baseQuery;
        $summary = [
            'total' => (clone $summaryQuery)->count(),
            'tenant_count' => (clone $summaryQuery)->distinct('tenant_id')->count('tenant_id'),
            'average_radius' => (int) round((clone $summaryQuery)->avg('radius') ?? 0),
        ];

        $tenants = Tenant::query()
            ->when($currentUser?->isManager(), fn ($query) => $query->whereKey($currentUser->tenant_id))
            ->orderBy('name')
            ->get(['id', 'name']);

        $selectedTenantName = $selectedTenantId
            ? $tenants->firstWhere('id', $selectedTenantId)?->name
            : null;

        return view('work_locations.index', [
            'workLocations' => $workLocations,
            'currentUser' => $currentUser,
            'search' => $search,
            'selectedTenantId' => $selectedTenantId,
            'selectedTenantName' => $selectedTenantName,
            'tenants' => $tenants,
            'summary' => $summary,
        ]);
    }

    public function create()
    {
        return view('work_locations.create', $this->getFormData(new WorkLocation()));
    }

    public function store(Request $request)
    {
        $tenantId = $this->resolveTenantId($request);
        $data = $this->validateData($request, null, $tenantId);
        $data['tenant_id'] = $tenantId;

        try {
            WorkLocation::create($data);
        } catch (Throwable $throwable) {
            report($throwable);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Lokasi kerja tidak dapat ditambahkan');
        }

        session()->flash('success', 'Lokasi kerja berhasil ditambahkan');

        return redirect()->route('work_locations.index');
    }

    public function edit(WorkLocation $work_location)
    {
        $this->ensureManagerCanAccessWorkLocation($work_location);

        return view('work_locations.edit', $this->getFormData($work_location));
    }

    public function update(Request $request, WorkLocation $work_location)
    {
        $this->ensureManagerCanAccessWorkLocation($work_location);
        $tenantId = $this->resolveTenantId($request, $work_location);
        $data = $this->validateData($request, $work_location, $tenantId);
        $data['tenant_id'] = $tenantId;

        $work_location->update($data);

        return redirect()->route('work_locations.index')->with('success', 'Work location updated successfully.');
    }

    public function destroy(WorkLocation $work_location)
    {
        $this->ensureManagerCanAccessWorkLocation($work_location);

        $work_location->delete();

        return redirect()->route('work_locations.index')->with('success', 'Work location deleted successfully.');
    }

    protected function validateData(Request $request, ?WorkLocation $workLocation, int $tenantId): array
    {
        return $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('work_locations', 'name')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->ignore($workLocation?->id),
            ],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['required', 'integer', 'min:1', 'max:100000'],
        ]);
    }

    protected function resolveTenantId(Request $request, ?WorkLocation $workLocation = null): int
    {
        $currentUser = $request->user() ?? auth()->user();

        if ($currentUser?->isManager()) {
            return (int) $currentUser->tenant_id;
        }

        return (int) ($request->integer('tenant_id') ?: $workLocation?->tenant_id);
    }

    protected function ensureManagerCanAccessWorkLocation(WorkLocation $workLocation): void
    {
        $currentUser = auth()->user();

        if ($currentUser?->isManager() && $currentUser->tenant_id !== $workLocation->tenant_id) {
            abort(403);
        }
    }

    protected function getFormData(WorkLocation $workLocation): array
    {
        $currentUser = auth()->user();
        $isManager = (bool) $currentUser?->isManager();
        $tenantId = $isManager
            ? $currentUser?->tenant_id
            : ($workLocation->tenant_id ?: $currentUser?->tenant_id);

        return [
            'workLocation' => $workLocation,
            'currentUser' => $currentUser,
            'isTenantLocked' => $isManager,
            'scopedTenantId' => $tenantId,
            'tenants' => $isManager && $tenantId
                ? Tenant::whereKey($tenantId)->get()
                : Tenant::orderBy('name')->get(),
        ];
    }
}
