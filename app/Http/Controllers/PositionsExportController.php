<?php

namespace App\Http\Controllers;

use App\Exports\PositionsExport;
use App\Exports\PositionsCsvExport;
use App\Models\Department;
use App\Models\Position;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

class PositionsExportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:positions');
    }

    public function csv(Request $request, Department $department)
    {
        $department = $this->resolveAccessibleDepartment($request, $department);
        $positions = $this->getPositionsForExport($department);
        $statusSummary = $this->getStatusSummary($positions);

        return Excel::download(
            new PositionsCsvExport($positions, [
                'department_name' => $department->name,
                'summary_title' => 'Summary Export Departemen',
                'active_count' => $statusSummary['active'],
                'inactive_count' => $statusSummary['inactive'],
            ]),
            $this->buildFilename($department, 'csv'),
            ExcelFormat::CSV
        );
    }

    public function xlsx(Request $request, Department $department)
    {
        $department = $this->resolveAccessibleDepartment($request, $department);
        $positions = $this->getPositionsForExport($department);
        $statusSummary = $this->getStatusSummary($positions);

        return Excel::download(
            new PositionsExport($positions, [
                'department_name' => $department->name,
                'summary_title' => 'Summary Export Departemen',
                'active_count' => $statusSummary['active'],
                'inactive_count' => $statusSummary['inactive'],
            ]),
            $this->buildFilename($department, 'xlsx'),
            ExcelFormat::XLSX
        );
    }

    public function indexCsv(Request $request)
    {
        [$search, $selectedTenantId, $selectedStatus, $selectedSortBy, $selectedSortDirection] = $this->resolveIndexFilters($request);
        $positions = $this->getFilteredPositionsForExport($request, $search, $selectedTenantId, $selectedStatus, $selectedSortBy, $selectedSortDirection);
        $statusSummary = $this->getStatusSummary($positions);

        return Excel::download(
            new PositionsCsvExport($positions, [
                'summary_title' => 'Summary Export Posisi',
                'active_count' => $statusSummary['active'],
                'inactive_count' => $statusSummary['inactive'],
            ]),
            $this->buildIndexFilename($selectedTenantId, $selectedStatus, 'csv'),
            ExcelFormat::CSV
        );
    }

    public function indexXlsx(Request $request)
    {
        [$search, $selectedTenantId, $selectedStatus, $selectedSortBy, $selectedSortDirection] = $this->resolveIndexFilters($request);
        $positions = $this->getFilteredPositionsForExport($request, $search, $selectedTenantId, $selectedStatus, $selectedSortBy, $selectedSortDirection);
        $statusSummary = $this->getStatusSummary($positions);

        return Excel::download(
            new PositionsExport($positions, [
                'summary_title' => 'Summary Export Posisi',
                'active_count' => $statusSummary['active'],
                'inactive_count' => $statusSummary['inactive'],
            ]),
            $this->buildIndexFilename($selectedTenantId, $selectedStatus, 'xlsx'),
            ExcelFormat::XLSX
        );
    }

    protected function resolveAccessibleDepartment(Request $request, Department $department): Department
    {
        /** @var User|null $currentUser */
        $currentUser = $request->user() ?? auth()->user();

        if ($currentUser?->isManager()) {
            abort_unless((int) $department->tenant_id === (int) $currentUser->tenant_id, 404);
        }

        return $department;
    }

    protected function getPositionsForExport(Department $department): Collection
    {
        return Position::query()
            ->where('department_id', $department->id)
            ->withCount('employees')
            ->orderBy('name')
            ->get();
    }

    protected function getFilteredPositionsForExport(
        Request $request,
        string $search,
        ?int $selectedTenantId,
        ?string $selectedStatus,
        string $selectedSortBy,
        string $selectedSortDirection
    ): Collection {
        $currentUser = $request->user() ?? auth()->user();

        $query = Position::query()
            ->select('positions.*')
            ->withCount('employees')
            ->leftJoin('departments', 'departments.id', '=', 'positions.department_id')
            ->leftJoin('tenants', 'tenants.id', '=', 'positions.tenant_id')
            ->when($currentUser?->isManager(), fn ($builder) => $builder->where('positions.tenant_id', $currentUser->tenant_id))
            ->when($selectedTenantId !== null, fn ($builder) => $builder->where('positions.tenant_id', $selectedTenantId))
            ->when($selectedStatus !== null, fn ($builder) => $builder->where('positions.status', $selectedStatus))
            ->when($search !== '', function ($builder) use ($search) {
                $builder->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('positions.name', 'like', "%{$search}%")
                        ->orWhere('positions.code', 'like', "%{$search}%")
                        ->orWhere('positions.description', 'like', "%{$search}%")
                        ->orWhere('departments.name', 'like', "%{$search}%")
                        ->orWhere('tenants.name', 'like', "%{$search}%");
                });
            });

        $this->applySorting($query, $selectedSortBy, $selectedSortDirection);

        return $query->get();
    }

    protected function buildFilename(Department $department, string $format): string
    {
        return 'positions_'.Str::slug($department->name, '_').'_'.now()->format('Ymd').'.'.$format;
    }

    protected function buildIndexFilename(?int $selectedTenantId, ?string $selectedStatus, string $format): string
    {
        $parts = ['positions'];

        if ($selectedTenantId !== null) {
            $resolvedTenantName = Tenant::query()->whereKey($selectedTenantId)->value('name');

            if ($resolvedTenantName) {
                $parts[] = Str::slug($resolvedTenantName, '_');
            }
        }

        if ($selectedStatus !== null) {
            $parts[] = $selectedStatus;
        }

        $parts[] = now()->format('Ymd');

        return implode('_', $parts).'.'.$format;
    }

    protected function resolveIndexFilters(Request $request): array
    {
        $search = trim((string) $request->string('search'));
        $selectedTenantId = $request->filled('tenant_id') ? (int) $request->integer('tenant_id') : null;
        $selectedStatus = $request->string('status')->toString();
        $selectedStatus = in_array($selectedStatus, ['active', 'inactive'], true) ? $selectedStatus : null;
        $selectedSortBy = $request->string('sort_by')->toString();
        $selectedSortBy = in_array($selectedSortBy, ['created_at', 'name', 'code', 'department_name', 'status'], true) ? $selectedSortBy : 'created_at';
        $selectedSortDirection = strtolower($request->string('sort_direction')->toString()) === 'asc' ? 'asc' : 'desc';

        return [$search, $selectedTenantId, $selectedStatus, $selectedSortBy, $selectedSortDirection];
    }

    protected function applySorting($query, string $selectedSortBy, string $selectedSortDirection): void
    {
        match ($selectedSortBy) {
            'name' => $query->orderBy('positions.name', $selectedSortDirection),
            'code' => $query->orderBy('positions.code', $selectedSortDirection),
            'department_name' => $query->orderBy('departments.name', $selectedSortDirection),
            'status' => $query->orderBy('positions.status', $selectedSortDirection),
            default => $query->orderBy('positions.created_at', $selectedSortDirection),
        };
    }

    protected function getStatusSummary(Collection $positions): array
    {
        return [
            'active' => $positions->where('status', 'active')->count(),
            'inactive' => $positions->where('status', 'inactive')->count(),
        ];
    }
}