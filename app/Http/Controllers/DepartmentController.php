<?php

namespace App\Http\Controllers;

use App\Exports\DepartmentsExport;
use App\Exports\DepartmentsImportTemplateExport;
use App\Imports\DepartmentsImport;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class DepartmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:departments');
    }

    public function index(Request $request)
    {
        $statuses = $this->statuses();
        $sortOptions = $this->sortOptions();
        [$search, $selectedTenantId, $selectedStatus, $selectedSortBy, $selectedSortDirection] = $this->resolveIndexFilters($request);

        $departmentsQuery = $this->buildDepartmentScopeQuery($search, $selectedTenantId, $selectedStatus);
        $this->applySorting($departmentsQuery, $selectedSortBy, $selectedSortDirection);

        $departments = (clone $departmentsQuery)
            ->paginate(10)
            ->withQueryString();

        $summaryQuery = $this->buildDepartmentScopeQuery($search, $selectedTenantId, $selectedStatus);

        $summary = [
            'total' => (clone $summaryQuery)->count(),
            'active' => (clone $summaryQuery)->where('departments.status', 'active')->count(),
            'inactive' => (clone $summaryQuery)->where('departments.status', 'inactive')->count(),
        ];

        $tenants = Tenant::orderBy('name')->get();
        $selectedTenantName = $selectedTenantId !== null
            ? optional($tenants->firstWhere('id', $selectedTenantId))->name
            : null;

        return view('departments.index', [
            'departments' => $departments,
            'department' => new Department(),
            'tenants' => $tenants,
            'statuses' => $statuses,
            'summary' => $summary,
            'search' => $search,
            'selectedTenantId' => $selectedTenantId,
            'selectedTenantName' => $selectedTenantName,
            'selectedStatus' => $selectedStatus,
            'selectedSortBy' => $selectedSortBy,
            'selectedSortDirection' => $selectedSortDirection,
            'sortOptions' => $sortOptions,
        ]);
    }

    public function export(Request $request)
    {
        [$search, $selectedTenantId, $selectedStatus, $selectedSortBy, $selectedSortDirection] = $this->resolveIndexFilters($request);
        $format = $this->resolveExportFormat($request);
        $tenantName = $selectedTenantId ? Tenant::whereKey($selectedTenantId)->value('name') : null;

        return Excel::download(
            new DepartmentsExport(
                $this->getDepartmentsForExport($search, $selectedTenantId, $selectedStatus, $selectedSortBy, $selectedSortDirection),
                [
                    'search' => $search,
                    'tenant_name' => $tenantName,
                    'status' => $selectedStatus,
                    'sort_by' => $selectedSortBy,
                    'sort_direction' => $selectedSortDirection,
                ]
            ),
            $this->buildExportFilename('departments-export', $format, [
                'tenant' => $tenantName,
                'status' => $selectedStatus,
                'search' => $search,
                'sort' => $selectedSortBy.'-'.$selectedSortDirection,
            ]),
            $format === 'xlsx' ? ExcelFormat::XLSX : ExcelFormat::CSV
        );
    }

    public function downloadImportTemplate()
    {
        return Excel::download(
            new DepartmentsImportTemplateExport(),
            'departments-import-template_'.now()->format('Ymd').'.xlsx',
            ExcelFormat::XLSX
        );
    }

    public function import(Request $request): RedirectResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'department_import_file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
            ],
            [
                'department_import_file.required' => 'File Excel wajib dipilih.',
                'department_import_file.mimes' => 'File import harus berformat Excel (.xlsx atau .xls).',
                'department_import_file.max' => 'Ukuran file import maksimal 5 MB.',
            ]
        );

        if ($validator->fails()) {
            return redirect()
                ->route('departments.index')
                ->withErrors($validator, 'importDepartments')
                ->with('open_department_import_modal', true);
        }

        try {
            $rows = Excel::toCollection(new DepartmentsImport(), $request->file('department_import_file'))->first() ?? collect();
        } catch (Throwable $exception) {
            return $this->redirectImportFailure([
                'File Excel tidak dapat dibaca. Pastikan format dan isi file sudah benar.',
            ]);
        }

        $rows = $rows instanceof Collection ? $rows : collect($rows);
        $rows = $rows
            ->map(fn ($row) => $row instanceof Collection ? $row->toArray() : (array) $row)
            ->filter(fn (array $row) => ! $this->isEmptyImportRow($row))
            ->values();

        if ($rows->isEmpty()) {
            return $this->redirectImportFailure([
                'File Excel tidak memiliki data departemen untuk diimport.',
            ]);
        }

        [$payloads, $errors] = $this->prepareDepartmentImportPayloads($rows);

        if (! empty($errors)) {
            return $this->redirectImportFailure($errors);
        }

        $createdCount = 0;
        $updatedCount = 0;

        DB::transaction(function () use ($payloads, &$createdCount, &$updatedCount): void {
            foreach ($payloads as $payload) {
                $existingDepartment = Department::query()
                    ->where('tenant_id', $payload['tenant_id'])
                    ->where('name', $payload['name'])
                    ->first();

                if ($existingDepartment) {
                    $existingDepartment->update($payload);
                    $updatedCount++;

                    continue;
                }

                Department::create($payload);
                $createdCount++;
            }
        });

        return redirect()
            ->route('departments.index')
            ->with('success', "Import departemen berhasil: {$createdCount} ditambahkan, {$updatedCount} diperbarui.");
    }

    public function create()
    {
        return view('departments.create', [
            'department' => new Department(),
            'tenants' => Tenant::orderBy('name')->get(),
            'statuses' => $this->statuses(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'name')->where(fn ($query) => $query->where('tenant_id', $request->tenant_id)),
            ],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('departments', 'code')->where(fn ($query) => $query->where('tenant_id', $request->tenant_id)),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(array_keys($this->statuses()))],
        ]);

        Department::create($data);

        return redirect()->route('departments.index')->with('success', 'Departemen berhasil ditambahkan.');
    }

    public function edit(Department $department)
    {
        return view('departments.edit', [
            'department' => $department,
            'tenants' => Tenant::orderBy('name')->get(),
            'statuses' => $this->statuses(),
        ]);
    }

    public function show(Department $department)
    {
        $department->load('tenant');

        return view('departments.show', [
            'department' => $department,
        ]);
    }

    public function update(Request $request, Department $department)
    {
        $data = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'name')
                    ->where(fn ($query) => $query->where('tenant_id', $request->tenant_id))
                    ->ignore($department->id),
            ],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('departments', 'code')
                    ->where(fn ($query) => $query->where('tenant_id', $request->tenant_id))
                    ->ignore($department->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(array_keys($this->statuses()))],
        ]);

        $department->update($data);

        return redirect()->route('departments.index')->with('success', 'Departemen berhasil diperbarui.');
    }

    public function destroy(Department $department)
    {
        $department->delete();

        return redirect()->route('departments.index')->with('success', 'Departemen berhasil dihapus.');
    }

    protected function statuses()
    {
        return [
            'active' => 'Aktif',
            'inactive' => 'Nonaktif',
        ];
    }

    protected function sortOptions(): array
    {
        return [
            'created_at' => 'Tanggal dibuat',
            'name' => 'Nama departemen',
            'code' => 'Kode departemen',
            'tenant' => 'Tenant',
            'status' => 'Status',
            'employees_count' => 'Jumlah karyawan',
            'positions_count' => 'Jumlah posisi',
        ];
    }

    protected function resolveIndexFilters(Request $request): array
    {
        $statuses = $this->statuses();
        $sortOptions = $this->sortOptions();

        $search = trim((string) $request->query('search', ''));
        $selectedTenantId = $request->filled('tenant_id') ? (int) $request->query('tenant_id') : null;
        $selectedStatus = trim((string) $request->query('status', ''));
        $selectedSortBy = trim((string) $request->query('sort_by', 'created_at'));
        $selectedSortDirection = strtolower(trim((string) $request->query('sort_direction', 'desc')));

        if ($selectedStatus === '' || ! array_key_exists($selectedStatus, $statuses)) {
            $selectedStatus = null;
        }

        if (! array_key_exists($selectedSortBy, $sortOptions)) {
            $selectedSortBy = 'created_at';
        }

        if (! in_array($selectedSortDirection, ['asc', 'desc'], true)) {
            $selectedSortDirection = 'desc';
        }

        return [$search, $selectedTenantId, $selectedStatus, $selectedSortBy, $selectedSortDirection];
    }

    protected function buildDepartmentScopeQuery(string $search = '', ?int $selectedTenantId = null, ?string $selectedStatus = null): Builder
    {
        $departmentsQuery = Department::query()
            ->select('departments.*')
            ->with('tenant')
            ->withCount('employees')
            ->selectSub(
                Employee::query()
                    ->selectRaw('COUNT(DISTINCT position_id)')
                    ->whereColumn('department_id', 'departments.id')
                    ->whereNotNull('position_id'),
                'positions_count'
            );

        if ($search !== '') {
            $departmentsQuery->where(function ($query) use ($search) {
                $query
                    ->where('departments.name', 'like', '%'.$search.'%')
                    ->orWhere('departments.code', 'like', '%'.$search.'%')
                    ->orWhere('departments.description', 'like', '%'.$search.'%')
                    ->orWhereHas('tenant', function ($tenantQuery) use ($search) {
                        $tenantQuery->where('name', 'like', '%'.$search.'%');
                    });
            });
        }

        if ($selectedTenantId !== null) {
            $departmentsQuery->where('departments.tenant_id', $selectedTenantId);
        }

        if ($selectedStatus !== null) {
            $departmentsQuery->where('departments.status', $selectedStatus);
        }

        return $departmentsQuery;
    }

    protected function applySorting(Builder $query, string $selectedSortBy, string $selectedSortDirection): void
    {
        match ($selectedSortBy) {
            'name', 'code', 'status', 'created_at' => $query->orderBy('departments.'.$selectedSortBy, $selectedSortDirection),
            'employees_count', 'positions_count' => $query->orderBy($selectedSortBy, $selectedSortDirection),
            'tenant' => $query->orderBy(
                Tenant::select('name')
                    ->whereColumn('tenants.id', 'departments.tenant_id')
                    ->limit(1),
                $selectedSortDirection
            ),
            default => $query->orderBy('departments.created_at', 'desc'),
        };

        if ($selectedSortBy !== 'name') {
            $query->orderBy('departments.name');
        }
    }

    protected function getDepartmentsForExport(
        string $search,
        ?int $selectedTenantId,
        ?string $selectedStatus,
        string $selectedSortBy,
        string $selectedSortDirection
    ) {
        $departmentsQuery = $this->buildDepartmentScopeQuery($search, $selectedTenantId, $selectedStatus);
        $this->applySorting($departmentsQuery, $selectedSortBy, $selectedSortDirection);

        return $departmentsQuery->get();
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

    protected function prepareDepartmentImportPayloads(Collection $rows): array
    {
        $errors = [];
        $payloads = [];
        $seenDepartmentNames = [];
        $seenDepartmentCodes = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $tenant = $this->resolveTenantFromImportRow($row);
            $name = $this->extractImportValue($row, ['name', 'nama_departemen', 'department_name', 'nama']);
            $code = $this->extractImportValue($row, ['code', 'kode', 'department_code']);
            $description = $this->extractImportValue($row, ['description', 'deskripsi']);
            $status = $this->normalizeImportedStatus($this->extractImportValue($row, ['status']));

            if (! $tenant) {
                $errors[] = "Baris {$rowNumber}: tenant tidak ditemukan. Gunakan kolom tenant_code atau tenant_name yang valid.";
                continue;
            }

            if ($name === null || $name === '') {
                $errors[] = "Baris {$rowNumber}: nama departemen wajib diisi.";
                continue;
            }

            if ($status === null) {
                $errors[] = "Baris {$rowNumber}: status harus bernilai active, inactive, aktif, atau nonaktif.";
                continue;
            }

            $nameKey = $tenant->id.'|'.Str::lower($name);

            if (isset($seenDepartmentNames[$nameKey])) {
                $errors[] = "Baris {$rowNumber}: nama departemen '{$name}' duplikat pada tenant {$tenant->name}.";
                continue;
            }

            $existingDepartment = Department::query()
                ->where('tenant_id', $tenant->id)
                ->where('name', $name)
                ->first();

            if ($code !== null && $code !== '') {
                $codeKey = $tenant->id.'|'.Str::lower($code);

                if (isset($seenDepartmentCodes[$codeKey])) {
                    $errors[] = "Baris {$rowNumber}: kode departemen '{$code}' duplikat pada tenant {$tenant->name}.";
                    continue;
                }

                $codeConflictExists = Department::query()
                    ->where('tenant_id', $tenant->id)
                    ->where('code', $code)
                    ->when($existingDepartment, fn ($query) => $query->whereKeyNot($existingDepartment->id))
                    ->exists();

                if ($codeConflictExists) {
                    $errors[] = "Baris {$rowNumber}: kode departemen '{$code}' sudah digunakan pada tenant {$tenant->name}.";
                    continue;
                }

                $seenDepartmentCodes[$codeKey] = true;
            }

            $seenDepartmentNames[$nameKey] = true;

            $payloads[] = [
                'tenant_id' => $tenant->id,
                'name' => $name,
                'code' => $code !== '' ? $code : null,
                'description' => $description !== '' ? $description : null,
                'status' => $status,
            ];
        }

        return [collect($payloads), $errors];
    }

    protected function extractImportValue(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $row)) {
                continue;
            }

            $value = trim((string) $row[$key]);

            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    protected function resolveTenantFromImportRow(array $row): ?Tenant
    {
        $tenantCode = $this->extractImportValue($row, ['tenant_code', 'kode_tenant']);
        $tenantName = $this->extractImportValue($row, ['tenant_name', 'tenant', 'nama_tenant']);

        if ($tenantCode !== null) {
            $tenant = Tenant::query()
                ->whereRaw('LOWER(code) = ?', [Str::lower($tenantCode)])
                ->first();

            if ($tenant) {
                return $tenant;
            }
        }

        if ($tenantName !== null) {
            return Tenant::query()
                ->whereRaw('LOWER(name) = ?', [Str::lower($tenantName)])
                ->first();
        }

        return null;
    }

    protected function normalizeImportedStatus(?string $status): ?string
    {
        if ($status === null || $status === '') {
            return 'active';
        }

        return match (Str::lower($status)) {
            'active', 'aktif' => 'active',
            'inactive', 'nonaktif' => 'inactive',
            default => null,
        };
    }

    protected function isEmptyImportRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    protected function redirectImportFailure(array $errors): RedirectResponse
    {
        return redirect()
            ->route('departments.index')
            ->with('error', 'Import departemen gagal. Periksa detail error pada modal import.')
            ->with('department_import_errors', $errors)
            ->with('open_department_import_modal', true);
    }
}
