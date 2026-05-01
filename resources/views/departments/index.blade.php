@extends('layouts.user_type.auth')

@section('suppress-global-flash', '1')

@section('content')

@php($activeDepartmentFilters = collect([
    $search !== '' ? 'Pencarian: '.$search : null,
    $selectedTenantName ? 'Tenant: '.$selectedTenantName : null,
    $selectedStatus && isset($statuses[$selectedStatus]) ? 'Status: '.$statuses[$selectedStatus] : null,
])->filter()->values())
@php($hasActiveDepartmentFilters = $activeDepartmentFilters->isNotEmpty())

<div class="row">
    <div class="col-12">
        <x-flash-messages />

        <div class="card mb-4 mx-4 shadow-xs">
            <div class="card-header pb-0">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <h5 class="mb-1">Daftar Departemen</h5>
                        <p class="text-sm text-secondary mb-0">Kelola master data departemen lengkap dengan pencarian cepat, filter tenant, dan status operasional.</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap align-items-center">
                        @if ($hasActiveDepartmentFilters)
                            <span class="badge bg-gradient-info">Filter aktif</span>
                            @foreach ($activeDepartmentFilters as $activeDepartmentFilter)
                                <span class="badge bg-gradient-light text-dark">{{ $activeDepartmentFilter }}</span>
                            @endforeach
                        @endif
                        <button type="button" class="btn btn-outline-dark btn-sm mb-0" data-bs-toggle="modal" data-bs-target="#importDepartmentModal" data-testid="btn-import-departments-xlsx">
                            <i class="fas fa-file-import me-1"></i> Import Excel
                        </button>
                        <a href="{{ route('departments.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}" class="btn btn-outline-success btn-sm mb-0" data-testid="btn-export-departments-xlsx">
                            <i class="fas fa-file-excel me-1"></i> Export Excel
                        </a>
                        <button type="button" class="btn bg-gradient-primary btn-sm mb-0" data-bs-toggle="modal" data-bs-target="#addDepartmentModal" data-testid="btn-add-department">
                            <i class="fas fa-plus me-1"></i> Tambah Departemen
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <div class="px-4 pt-4">
                    <div class="row g-3 mb-4">
                        <div class="col-xl-4 col-md-6">
                            <div class="card border shadow-xs h-100" data-testid="departments-summary-total">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Total Departemen</p>
                                    <h5 class="mb-0">{{ $summary['total'] }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <div class="card border shadow-xs h-100" data-testid="departments-summary-active">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Departemen Aktif</p>
                                    <h5 class="mb-0 text-success">{{ $summary['active'] }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-12">
                            <div class="card border shadow-xs h-100" data-testid="departments-summary-inactive">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Departemen Nonaktif</p>
                                    <h5 class="mb-0 text-secondary">{{ $summary['inactive'] }}</h5>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border border-radius-xl p-3 mb-4 bg-gray-100">
                        <form action="{{ route('departments.index') }}" method="GET" class="row g-3 align-items-end" data-testid="departments-filter-form">
                            <div class="col-lg-4 col-md-6">
                                <label for="search" class="form-label">Cari Departemen</label>
                                <div class="input-group">
                                    <span class="input-group-text text-body"><i class="fas fa-search" aria-hidden="true"></i></span>
                                    <input
                                        type="text"
                                        id="search"
                                        name="search"
                                        class="form-control"
                                        value="{{ $search }}"
                                        placeholder="Cari nama, kode, tenant, atau deskripsi"
                                        data-testid="departments-search-input"
                                    >
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label for="tenant_id" class="form-label">Tenant</label>
                                <select name="tenant_id" id="tenant_id" class="form-control" data-testid="departments-tenant-filter">
                                    <option value="">Semua Tenant</option>
                                    @foreach ($tenants as $tenant)
                                        <option value="{{ $tenant->id }}" @selected($selectedTenantId === $tenant->id)>{{ $tenant->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="form-control" data-testid="departments-status-filter">
                                    <option value="">Semua Status</option>
                                    @foreach ($statuses as $statusValue => $statusLabel)
                                        <option value="{{ $statusValue }}" @selected($selectedStatus === $statusValue)>{{ $statusLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-4 col-md-12">
                                <div class="d-flex gap-2 justify-content-lg-end mt-lg-4">
                                    <button type="submit" class="btn bg-gradient-dark mb-0" data-testid="btn-apply-department-filter">
                                        <i class="fas fa-filter me-1"></i> Terapkan
                                    </button>
                                    <a href="{{ route('departments.index') }}" class="btn btn-light mb-0" data-testid="btn-reset-department-filter">
                                        Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                @if ($departments->isEmpty())
                    <div class="text-center py-5" data-testid="{{ $hasActiveDepartmentFilters ? 'departments-filter-empty-state' : 'departments-empty-state' }}">
                        <i class="fas fa-sitemap fa-3x text-secondary mb-3"></i>
                        @if ($hasActiveDepartmentFilters)
                            <p class="text-secondary mb-1">Tidak ada departemen yang cocok dengan pencarian atau filter saat ini.</p>
                            <p class="text-sm text-secondary mb-3">Coba ubah kata kunci, tenant, atau status untuk melihat hasil lain.</p>
                            <a href="{{ route('departments.index') }}" class="btn btn-light btn-sm mb-0">Reset Filter</a>
                        @else
                            <p class="text-secondary mb-0">Belum ada departemen, silakan tambah terlebih dahulu</p>
                        @endif
                    </div>
                @else
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0" data-testid="departments-table">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4 text-start">Nama Departemen</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Kode</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Tenant</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">
                                        <span class="d-inline-flex align-items-center gap-1">
                                            Status
                                            <i class="fas fa-info-circle text-secondary text-xs" data-bs-toggle="tooltip" title="Status menunjukkan apakah departemen masih aktif digunakan."></i>
                                        </span>
                                    </th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Deskripsi</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Ringkasan</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($departments as $department)
                                    <tr>
                                        <td class="ps-4 text-start">
                                            <h6 class="mb-0 text-sm">{{ $department->name }}</h6>
                                        </td>
                                        <td class="text-start">
                                            <span class="text-sm font-weight-bold">{{ $department->code ?? '—' }}</span>
                                        </td>
                                        <td class="text-start">
                                            <span class="text-secondary text-sm">{{ $department->tenant?->name ?? '—' }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $department->status === 'active' ? 'bg-gradient-success' : 'bg-gradient-secondary' }}">{{ $department->status === 'active' ? 'Aktif' : 'Nonaktif' }}</span>
                                        </td>
                                        <td class="text-start">
                                            <span class="text-secondary text-sm">{{ \Illuminate\Support\Str::limit($department->description ?? '—', 80) }}</span>
                                        </td>
                                        <td class="text-start">
                                            <div class="d-flex flex-wrap gap-2">
                                                <span class="badge bg-success" data-testid="department-employees-count-{{ $department->id }}">{{ $department->employees_count }} Karyawan</span>
                                                <span class="badge bg-info" data-testid="department-positions-count-{{ $department->id }}">{{ $department->positions_count ?? 0 }} Posisi</span>
                                            </div>
                                        </td>
                                        <td class="text-start">
                                            <div class="d-flex align-items-center gap-3">
                                                <a href="{{ route('departments.show', $department) }}" class="mx-1" data-bs-toggle="tooltip" title="Lihat departemen" data-testid="btn-view-department-{{ $department->id }}">
                                                    <i class="fas fa-eye text-info"></i>
                                                </a>
                                                <a href="{{ route('departments.edit', $department) }}" class="mx-1" data-bs-toggle="tooltip" title="Edit departemen" data-testid="btn-edit-department-{{ $department->id }}">
                                                    <i class="fas fa-edit text-secondary"></i>
                                                </a>
                                                <button type="button" class="border-0 bg-transparent p-0 mx-1" data-bs-toggle="modal" data-bs-target="#deleteDepartmentIndexModal-{{ $department->id }}" title="Hapus departemen" data-testid="btn-delete-department-{{ $department->id }}">
                                                    <i class="fas fa-trash text-danger"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <div class="modal fade" id="deleteDepartmentIndexModal-{{ $department->id }}" tabindex="-1" aria-labelledby="deleteDepartmentIndexModalLabel-{{ $department->id }}" aria-hidden="true" data-testid="department-index-delete-modal-{{ $department->id }}">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="deleteDepartmentIndexModalLabel-{{ $department->id }}">Konfirmasi Hapus Departemen</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Apakah Anda yakin ingin menghapus departemen <strong>{{ $department->name }}</strong>?
                                                </div>
                                                <div class="modal-footer">
                                                    <form action="{{ route('departments.destroy', $department) }}" method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button" class="btn btn-light mb-0" data-bs-dismiss="modal">
                                                            <i class="fas fa-times me-1"></i> Batal
                                                        </button>
                                                        <button type="submit" class="btn btn-danger mb-0" data-testid="confirm-delete-department-{{ $department->id }}">
                                                            <i class="fas fa-trash me-1"></i> Hapus
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 pt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <p class="text-sm text-secondary mb-0">Menampilkan {{ $departments->count() }} dari total {{ $departments->total() }} departemen.</p>
                        {{ $departments->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="importDepartmentModal" tabindex="-1" aria-labelledby="importDepartmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="importDepartmentModalLabel">Import Departemen dari Excel</h5>
                    <p class="text-sm text-secondary mb-0">Gunakan file Microsoft Excel `.xlsx` atau `.xls` dengan heading yang sesuai.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                @if (session('department_import_errors'))
                    <div class="alert alert-danger text-white" data-testid="department-import-errors">
                        <strong>Import gagal.</strong>
                        <ul class="mb-0 mt-2 ps-3">
                            @foreach (session('department_import_errors') as $departmentImportError)
                                <li>{{ $departmentImportError }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 border border-radius-lg p-3 bg-gray-100 mb-4">
                    <div>
                        <p class="text-sm font-weight-bold mb-1">Butuh format siap pakai?</p>
                        <p class="text-sm text-secondary mb-0">Unduh template Excel, isi datanya, lalu upload kembali di form import.</p>
                    </div>
                    <a href="{{ route('departments.import.template') }}" class="btn btn-outline-success btn-sm mb-0" data-testid="btn-download-departments-template">
                        <i class="fas fa-download me-1"></i> Download Template
                    </a>
                </div>

                <div class="border border-radius-lg p-3 bg-gray-100 mb-4" data-testid="department-import-template-info">
                    <p class="text-sm font-weight-bold mb-2">Kolom yang didukung</p>
                    <div class="table-responsive">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Kolom</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>tenant_code</code> atau <code>tenant_name</code></td>
                                    <td class="text-sm text-secondary">Wajib. Disarankan gunakan <code>tenant_code</code>.</td>
                                </tr>
                                <tr>
                                    <td><code>name</code></td>
                                    <td class="text-sm text-secondary">Wajib. Nama departemen.</td>
                                </tr>
                                <tr>
                                    <td><code>code</code></td>
                                    <td class="text-sm text-secondary">Opsional. Kode departemen per tenant.</td>
                                </tr>
                                <tr>
                                    <td><code>description</code></td>
                                    <td class="text-sm text-secondary">Opsional. Deskripsi singkat departemen.</td>
                                </tr>
                                <tr>
                                    <td><code>status</code></td>
                                    <td class="text-sm text-secondary">Opsional. Gunakan <code>active</code>, <code>inactive</code>, <code>aktif</code>, atau <code>nonaktif</code>.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <form action="{{ route('departments.import') }}" method="POST" enctype="multipart/form-data" data-testid="departments-import-form">
                    @csrf
                    <div class="mb-3">
                        <label for="department_import_file" class="form-label">File Excel</label>
                        <input
                            type="file"
                            name="department_import_file"
                            id="department_import_file"
                            class="form-control @if ($errors->importDepartments->has('department_import_file')) is-invalid @endif"
                            accept=".xlsx,.xls"
                            data-testid="departments-import-file"
                        >
                        @if ($errors->importDepartments->has('department_import_file'))
                            <div class="invalid-feedback d-block">
                                {{ $errors->importDepartments->first('department_import_file') }}
                            </div>
                        @endif
                        <small class="text-secondary">Baris pertama harus berisi heading kolom. Import akan membuat baru atau memperbarui departemen berdasarkan nama pada tenant yang sama.</small>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light mb-0" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn bg-gradient-dark mb-0" data-testid="btn-submit-departments-import">
                            <i class="fas fa-upload me-1"></i> Import Sekarang
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addDepartmentModal" tabindex="-1" aria-labelledby="addDepartmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDepartmentModalLabel">Tambah Departemen Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                @if ($tenants->isEmpty())
                    <div class="border border-secondary border-radius-md p-4 text-center bg-gray-100" data-testid="departments-modal-empty-state">
                        <i class="fas fa-building text-secondary fa-2x mb-3"></i>
                        <h6 class="mb-2">Belum ada tenant tersedia</h6>
                        <p class="text-sm text-secondary mb-0">Buat tenant terlebih dahulu sebelum menambahkan departemen baru.</p>
                    </div>
                @else
                    <form action="{{ route('departments.store') }}" method="POST" data-testid="departments-index-create-form" autocomplete="off">
                        @csrf
                        @include('departments._form')
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn btn-light mb-0" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i> Batal</button>
                            <button type="submit" class="btn bg-gradient-primary mb-0"><i class="fas fa-save me-1"></i> Simpan</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var modalElement = document.getElementById('addDepartmentModal');
        var importModalElement = document.getElementById('importDepartmentModal');
        var hasValidationErrors = @json($errors->has('name') || $errors->has('tenant_id') || $errors->has('code') || $errors->has('description') || $errors->has('status'));
        var shouldOpenImportModal = @json(session('open_department_import_modal') || $errors->importDepartments->has('department_import_file'));

        if (! modalElement || typeof bootstrap === 'undefined') {
            return;
        }

        function resetCreateDepartmentForm() {
            var form = modalElement.querySelector('form[data-testid="departments-index-create-form"]');

            if (! form) {
                return;
            }

            form.reset();

            var nameInput = form.querySelector('input[name="name"]');
            var codeInput = form.querySelector('input[name="code"]');
            var tenantSelect = form.querySelector('select[name="tenant_id"]');
            var descriptionInput = form.querySelector('textarea[name="description"]');
            var statusInput = form.querySelector('input[name="status"][value="active"]');

            if (nameInput) {
                nameInput.value = '';
            }

            if (codeInput) {
                codeInput.value = '';
            }

            if (tenantSelect) {
                tenantSelect.value = '';
            }

            if (descriptionInput) {
                descriptionInput.value = '';
            }

            if (statusInput) {
                statusInput.checked = true;
            }

            form.querySelectorAll('.is-invalid').forEach(function (field) {
                field.classList.remove('is-invalid');
            });

            form.querySelectorAll('.invalid-feedback').forEach(function (feedback) {
                feedback.style.display = '';
            });
        }

        modalElement.addEventListener('show.bs.modal', function () {
            if (! hasValidationErrors) {
                resetCreateDepartmentForm();
            }
        });

        modalElement.addEventListener('hidden.bs.modal', function () {
            hasValidationErrors = false;
            resetCreateDepartmentForm();
        });

        @if ($errors->has('name') || $errors->has('tenant_id') || $errors->has('code') || $errors->has('description') || $errors->has('status'))
            bootstrap.Modal.getOrCreateInstance(modalElement).show();
        @endif

        if (importModalElement && shouldOpenImportModal) {
            bootstrap.Modal.getOrCreateInstance(importModalElement).show();
        }
    });
</script>

@endsection
