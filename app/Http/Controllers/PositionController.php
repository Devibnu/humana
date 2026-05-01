<?php

namespace App\Http\Controllers;

use App\Exports\PositionsImportTemplateExport;
use App\Imports\PositionsImport;
use App\Models\Department;
use App\Models\Position;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class PositionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:positions');
    }

    public function index(Request $request)
    {
        $statuses = $this->statuses();
        $sortOptions = $this->sortOptions();
        [$search, $selectedTenantId, $selectedStatus, $selectedSortBy, $selectedSortDirection] = $this->resolveIndexFilters($request);

        $positionsQuery = $this->buildPositionScopeQuery($request, $search, $selectedTenantId, $selectedStatus);
        $this->applySorting($positionsQuery, $selectedSortBy, $selectedSortDirection);

        $positions = (clone $positionsQuery)
            ->paginate(10)
            ->withQueryString();

        $summaryQuery = $this->buildPositionScopeQuery($request, $search, $selectedTenantId, $selectedStatus);

        $summary = [
            'total' => (clone $summaryQuery)->count(),
            'active' => (clone $summaryQuery)->where('positions.status', 'active')->count(),
            'inactive' => (clone $summaryQuery)->where('positions.status', 'inactive')->count(),
        ];

        $tenants = Tenant::orderBy('name')->get();
        $selectedTenantName = $selectedTenantId !== null
            ? optional($tenants->firstWhere('id', $selectedTenantId))->name
            : null;

        return view('positions.index', [
            'positions' => $positions,
            'summary' => $summary,
            'position' => new Position(),
            'tenants' => $tenants,
            'departments' => Department::with('tenant')->orderBy('name')->get(),
            'statuses' => $statuses,
            'search' => $search,
            'selectedTenantId' => $selectedTenantId,
            'selectedTenantName' => $selectedTenantName,
            'selectedStatus' => $selectedStatus,
            'selectedSortBy' => $selectedSortBy,
            'selectedSortDirection' => $selectedSortDirection,
            'sortOptions' => $sortOptions,
        ]);
    }

    public function create()
    {
        return view('positions.create', [
            'position' => new Position(),
            'tenants' => Tenant::orderBy('name')->get(),
            'departments' => Department::with('tenant')->orderBy('name')->get(),
            'statuses' => $this->statuses(),
        ]);
    }

    public function downloadImportTemplate()
    {
        return Excel::download(
            new PositionsImportTemplateExport(),
            'positions-import-template_'.now()->format('Ymd').'.xlsx',
            ExcelFormat::XLSX
        );
    }

    public function import(Request $request): RedirectResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'position_import_file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
            ],
            [
                'position_import_file.required' => 'File Excel wajib dipilih.',
                'position_import_file.mimes' => 'File import harus berformat Excel (.xlsx atau .xls).',
                'position_import_file.max' => 'Ukuran file import maksimal 5 MB.',
            ]
        );

        if ($validator->fails()) {
            return redirect()
                ->route('positions.index')
                ->withErrors($validator, 'importPositions')
                ->with('open_position_import_modal', true);
        }

        try {
            $rows = Excel::toCollection(new PositionsImport(), $request->file('position_import_file'))->first() ?? collect();
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
                'File Excel tidak memiliki data posisi untuk diimport.',
            ]);
        }

        [$payloads, $errors] = $this->preparePositionImportPayloads($rows);

        if (! empty($errors)) {
            return $this->redirectImportFailure($errors);
        }

        $createdCount = 0;
        $updatedCount = 0;

        DB::transaction(function () use ($payloads, &$createdCount, &$updatedCount): void {
            foreach ($payloads as $payload) {
                $existingPosition = Position::query()
                    ->where('tenant_id', $payload['tenant_id'])
                    ->where('name', $payload['name'])
                    ->first();

                if ($existingPosition) {
                    $existingPosition->update($payload);
                    $updatedCount++;

                    continue;
                }

                Position::create($payload);
                $createdCount++;
            }
        });

        return redirect()
            ->route('positions.index')
            ->with('success', "Import posisi berhasil: {$createdCount} ditambahkan, {$updatedCount} diperbarui.");
    }

    public function store(Request $request, ?Department $department = null)
    {
        if ($department) {
            $validator = Validator::make($request->all(), [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('positions', 'name')->where(fn ($query) => $query->where('tenant_id', $department->tenant_id)),
                ],
                'code' => [
                    'nullable',
                    'string',
                    'max:50',
                    Rule::unique('positions', 'code')->where(fn ($query) => $query->where('tenant_id', $department->tenant_id)),
                ],
                'description' => ['nullable', 'string', 'max:1000'],
            ]);

            if ($validator->fails()) {
                session()->flash('error', 'Terjadi kesalahan, silakan coba lagi');

                return redirect()
                    ->route('departments.show', $department)
                    ->withErrors($validator, 'addPosition')
                    ->withInput();
            }

            $data = $validator->validated();

            $data['tenant_id'] = $department->tenant_id;
            $data['department_id'] = $department->id;
            $data['status'] = 'active';

            try {
                Position::create($data);

                session()->flash('success', 'Posisi berhasil ditambahkan');

                return redirect()->route('departments.show', $department);
            } catch (Throwable $exception) {
                session()->flash('error', 'Terjadi kesalahan, silakan coba lagi');

                return redirect()
                    ->route('departments.show', $department)
                    ->withInput();
            }
        }

        $data = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'department_id' => [
                'required',
                Rule::exists('departments', 'id')->where(fn ($query) => $query->where('tenant_id', $request->tenant_id)),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('positions', 'name')->where(fn ($query) => $query->where('tenant_id', $request->tenant_id)),
            ],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('positions', 'code')->where(fn ($query) => $query->where('tenant_id', $request->tenant_id)),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(array_keys($this->statuses()))],
        ]);

        try {
            Position::create($data);
            session()->flash('success', 'Posisi berhasil ditambahkan');

            return redirect()->route('positions.index');
        } catch (Throwable $exception) {
            return back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan, silakan coba lagi');
        }
    }

    public function show(Position $position)
    {
        $position->load(['tenant', 'department'])->loadCount('employees');

        return view('positions.show', [
            'position' => $position,
        ]);
    }

    public function edit(Position $position)
    {
        return view('positions.edit', [
            'position' => $position,
            'tenants' => Tenant::orderBy('name')->get(),
            'departments' => Department::with('tenant')->orderBy('name')->get(),
            'statuses' => $this->statuses(),
        ]);
    }

    public function update(Request $request, $departmentOrPosition, ?Position $position = null)
    {
        if ($departmentOrPosition instanceof Position) {
            $position = $departmentOrPosition;
            $departmentId = null;
        } else {
            $departmentId = $departmentOrPosition;
            $position ??= Position::query()->findOrFail($request->route('position'));
        }

        if ($departmentId) {
            $department = Department::query()->findOrFail($departmentId);
            $errorBag = 'editPosition'.$position->id;

            $validator = Validator::make($request->all(), [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('positions', 'name')
                        ->where(fn ($query) => $query->where('tenant_id', $department->tenant_id))
                        ->ignore($position->id),
                ],
                'code' => [
                    'nullable',
                    'string',
                    'max:50',
                    Rule::unique('positions', 'code')
                        ->where(fn ($query) => $query->where('tenant_id', $department->tenant_id))
                        ->ignore($position->id),
                ],
                'description' => ['nullable', 'string', 'max:1000'],
                'edit_position_id' => ['nullable', 'integer'],
            ]);

            if ($validator->fails()) {
                session()->flash('error', 'Terjadi kesalahan, silakan coba lagi');

                return redirect()
                    ->route('departments.show', $department)
                    ->withErrors($validator, $errorBag)
                    ->withInput();
            }

            $data = $validator->validated();

            unset($data['edit_position_id']);

            $data['tenant_id'] = $department->tenant_id;
            $data['department_id'] = $department->id;
            $data['status'] = $position->status ?? 'active';

            try {
                $position->update($data);

                session()->flash('success', 'Posisi berhasil diperbarui');

                return redirect()->route('departments.show', $department);
            } catch (Throwable $exception) {
                session()->flash('error', 'Terjadi kesalahan, silakan coba lagi');

                return redirect()
                    ->route('departments.show', $department)
                    ->withInput();
            }
        }

        $data = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'department_id' => [
                'required',
                Rule::exists('departments', 'id')->where(fn ($query) => $query->where('tenant_id', $request->tenant_id)),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('positions', 'name')
                    ->where(fn ($query) => $query->where('tenant_id', $request->tenant_id))
                    ->ignore($position->id),
            ],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('positions', 'code')
                    ->where(fn ($query) => $query->where('tenant_id', $request->tenant_id))
                    ->ignore($position->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(array_keys($this->statuses()))],
        ]);

        try {
            $position->update($data);

            return $this->redirectAfterMutation($request, 'Posisi berhasil diperbarui');
        } catch (Throwable $exception) {
            return back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan, silakan coba lagi');
        }
    }

    public function destroy(Request $request, $departmentOrPosition, ?Position $position = null)
    {
        if ($departmentOrPosition instanceof Position) {
            $position = $departmentOrPosition;
            $departmentId = null;
        } else {
            $departmentId = $departmentOrPosition;
        }

        if ($departmentId) {
            $department = Department::query()->findOrFail($departmentId);
            $routePosition = $position ?? $request->route('position');

            if ($routePosition instanceof Position) {
                $position = Position::query()
                    ->where('department_id', $department->id)
                    ->find($routePosition->id);
            } else {
                $position = Position::query()
                    ->where('department_id', $department->id)
                    ->find($routePosition);
            }

            if (! $position) {
                return redirect()
                    ->route('departments.show', $department)
                    ->with('error', 'Terjadi kesalahan, posisi tidak dapat dihapus');
            }

            try {
                $position->delete();

                return redirect()
                    ->route('departments.show', $department)
                    ->with('success', 'Posisi berhasil dihapus');
            } catch (Throwable $exception) {
                return redirect()
                    ->route('departments.show', $department)
                    ->with('error', 'Terjadi kesalahan, posisi tidak dapat dihapus');
            }
        }

        try {
            $position->delete();

            return $this->redirectAfterMutation($request, 'Posisi berhasil dihapus');
        } catch (Throwable $exception) {
            $redirectTo = $request->input('redirect_to');

            if (is_string($redirectTo) && $redirectTo !== '') {
                return redirect()->to($redirectTo)->with('error', 'Terjadi kesalahan, silakan coba lagi');
            }

            return redirect()->route('positions.index')->with('error', 'Terjadi kesalahan, silakan coba lagi');
        }
    }

    protected function statuses()
    {
        return [
            'active' => 'Aktif',
            'inactive' => 'Non-Aktif',
        ];
    }

    protected function sortOptions(): array
    {
        return [
            'created_at' => 'Terbaru Ditambahkan',
            'name' => 'Nama Posisi',
            'code' => 'Kode Posisi',
            'department_name' => 'Nama Departemen',
            'status' => 'Status',
        ];
    }

    protected function resolveIndexFilters(Request $request): array
    {
        $statuses = array_keys($this->statuses());
        $sortOptions = array_keys($this->sortOptions());

        $search = trim((string) $request->string('search'));
        $selectedTenantId = $request->filled('tenant_id') ? (int) $request->integer('tenant_id') : null;
        $selectedStatus = $request->string('status')->toString();
        $selectedStatus = in_array($selectedStatus, $statuses, true) ? $selectedStatus : null;
        $selectedSortBy = $request->string('sort_by')->toString();
        $selectedSortBy = in_array($selectedSortBy, $sortOptions, true) ? $selectedSortBy : 'created_at';
        $selectedSortDirection = strtolower($request->string('sort_direction')->toString()) === 'asc' ? 'asc' : 'desc';

        return [$search, $selectedTenantId, $selectedStatus, $selectedSortBy, $selectedSortDirection];
    }

    protected function buildPositionScopeQuery(Request $request, string $search, ?int $selectedTenantId, ?string $selectedStatus)
    {
        $currentUser = $request->user() ?? auth()->user();

        return Position::query()
            ->select('positions.*')
            ->with(['tenant', 'department'])
            ->withCount('employees')
            ->leftJoin('departments', 'departments.id', '=', 'positions.department_id')
            ->leftJoin('tenants', 'tenants.id', '=', 'positions.tenant_id')
            ->when($currentUser?->isManager(), fn ($query) => $query->where('positions.tenant_id', $currentUser->tenant_id))
            ->when($selectedTenantId !== null, fn ($query) => $query->where('positions.tenant_id', $selectedTenantId))
            ->when($selectedStatus !== null, fn ($query) => $query->where('positions.status', $selectedStatus))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('positions.name', 'like', "%{$search}%")
                        ->orWhere('positions.code', 'like', "%{$search}%")
                        ->orWhere('positions.description', 'like', "%{$search}%")
                        ->orWhere('departments.name', 'like', "%{$search}%")
                        ->orWhere('tenants.name', 'like', "%{$search}%");
                });
            });
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

    protected function preparePositionImportPayloads(Collection $rows): array
    {
        $errors = [];
        $payloads = [];
        $seenPositionNames = [];
        $seenPositionCodes = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $tenant = $this->resolveTenantFromImportRow($row);
            $department = $tenant ? $this->resolveDepartmentFromImportRow($row, $tenant) : null;
            $name = $this->extractImportValue($row, ['name', 'nama_posisi', 'position_name', 'nama']);
            $code = $this->extractImportValue($row, ['code', 'kode', 'position_code']);
            $description = $this->extractImportValue($row, ['description', 'deskripsi']);
            $status = $this->normalizeImportedStatus($this->extractImportValue($row, ['status']));

            if (! $tenant) {
                $errors[] = "Baris {$rowNumber}: tenant tidak ditemukan. Gunakan kolom tenant_code atau tenant_name yang valid.";
                continue;
            }

            if (! $department) {
                $errors[] = "Baris {$rowNumber}: departemen tidak ditemukan. Gunakan kolom department_code atau department_name yang valid pada tenant {$tenant->name}.";
                continue;
            }

            if ($name === null || $name === '') {
                $errors[] = "Baris {$rowNumber}: nama posisi wajib diisi.";
                continue;
            }

            if ($status === null) {
                $errors[] = "Baris {$rowNumber}: status harus bernilai active, inactive, aktif, atau nonaktif.";
                continue;
            }

            $nameKey = $tenant->id.'|'.Str::lower($name);

            if (isset($seenPositionNames[$nameKey])) {
                $errors[] = "Baris {$rowNumber}: nama posisi '{$name}' duplikat pada tenant {$tenant->name}.";
                continue;
            }

            $existingPosition = Position::query()
                ->where('tenant_id', $tenant->id)
                ->where('name', $name)
                ->first();

            if ($code !== null && $code !== '') {
                $codeKey = $tenant->id.'|'.Str::lower($code);

                if (isset($seenPositionCodes[$codeKey])) {
                    $errors[] = "Baris {$rowNumber}: kode posisi '{$code}' duplikat pada tenant {$tenant->name}.";
                    continue;
                }

                $codeConflictExists = Position::query()
                    ->where('tenant_id', $tenant->id)
                    ->where('code', $code)
                    ->when($existingPosition, fn ($query) => $query->whereKeyNot($existingPosition->id))
                    ->exists();

                if ($codeConflictExists) {
                    $errors[] = "Baris {$rowNumber}: kode posisi '{$code}' sudah digunakan pada tenant {$tenant->name}.";
                    continue;
                }

                $seenPositionCodes[$codeKey] = true;
            }

            $seenPositionNames[$nameKey] = true;

            $payloads[] = [
                'tenant_id' => $tenant->id,
                'department_id' => $department->id,
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

    protected function resolveDepartmentFromImportRow(array $row, Tenant $tenant): ?Department
    {
        $departmentCode = $this->extractImportValue($row, ['department_code', 'kode_departemen']);
        $departmentName = $this->extractImportValue($row, ['department_name', 'departemen', 'nama_departemen']);

        if ($departmentCode !== null) {
            $department = Department::query()
                ->where('tenant_id', $tenant->id)
                ->whereRaw('LOWER(code) = ?', [Str::lower($departmentCode)])
                ->first();

            if ($department) {
                return $department;
            }
        }

        if ($departmentName !== null) {
            return Department::query()
                ->where('tenant_id', $tenant->id)
                ->whereRaw('LOWER(name) = ?', [Str::lower($departmentName)])
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
            ->route('positions.index')
            ->with('error', 'Import posisi gagal. Periksa detail error pada modal import.')
            ->with('position_import_errors', $errors)
            ->with('open_position_import_modal', true);
    }

    protected function redirectAfterMutation(Request $request, string $message)
    {
        $redirectTo = $request->input('redirect_to');

        if (is_string($redirectTo) && $redirectTo !== '') {
            return redirect()->to($redirectTo)->with('success', $message);
        }

        return redirect()->route('positions.index')->with('success', $message);
    }
}