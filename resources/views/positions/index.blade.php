@extends('layouts.user_type.auth')

@section('content')

@php($activePositionFilters = collect([
    $search !== '' ? 'Pencarian: '.$search : null,
    $selectedTenantName ? 'Tenant: '.$selectedTenantName : null,
    $selectedStatus && isset($statuses[$selectedStatus]) ? 'Status: '.$statuses[$selectedStatus] : null,
])->filter()->values())
@php($hasActivePositionFilters = $activePositionFilters->isNotEmpty())

<div class="row">
    <div class="col-12">
        <x-flash-messages />
        <div class="card mb-4 mx-4 shadow-xs">
            <div class="card-header pb-0">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <h5 class="mb-1">Daftar Posisi</h5>
                        <p class="text-sm text-secondary mb-0">Kelola master data posisi lengkap dengan pencarian cepat, filter tenant, dan status operasional.</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap align-items-center">
                        @if ($hasActivePositionFilters)
                            <span class="badge bg-gradient-info">Filter aktif</span>
                            @foreach ($activePositionFilters as $activePositionFilter)
                                <span class="badge bg-gradient-light text-dark">{{ $activePositionFilter }}</span>
                            @endforeach
                        @endif
                        <button type="button" class="btn btn-outline-dark btn-sm mb-0" data-bs-toggle="modal" data-bs-target="#importPositionModal" data-testid="btn-import-positions-xlsx">
                            <i class="fas fa-file-import me-1"></i> Import Excel
                        </button>
                        <a href="{{ route('positions.export.xlsx', request()->query()) }}" class="btn btn-outline-success btn-sm mb-0" data-testid="btn-export-positions-xlsx">
                            <i class="fas fa-file-excel me-1"></i> Export Excel
                        </a>
                        <button type="button" class="btn bg-gradient-primary btn-sm mb-0" data-bs-toggle="modal" data-bs-target="#addPositionIndexModal" data-testid="btn-open-add-position-modal">
                            <i class="fas fa-plus me-1"></i> Tambah Posisi
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <div class="px-4 pt-4">
                    <div class="row g-3 mb-4">
                        <div class="col-xl-4 col-md-6">
                            <div class="card border shadow-xs h-100" data-testid="positions-summary-total">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Total Posisi</p>
                                    <h5 class="mb-0">{{ $summary['total'] }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <div class="card border shadow-xs h-100" data-testid="positions-summary-active">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Posisi Aktif</p>
                                    <h5 class="mb-0 text-success">{{ $summary['active'] }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-12">
                            <div class="card border shadow-xs h-100" data-testid="positions-summary-inactive">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Posisi Nonaktif</p>
                                    <h5 class="mb-0 text-secondary">{{ $summary['inactive'] }}</h5>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border border-radius-xl p-3 mb-4 bg-gray-100">
                        <form action="{{ route('positions.index') }}" method="GET" class="row g-3 align-items-end" data-testid="positions-filter-form">
                            <div class="col-lg-4 col-md-6">
                                <label for="search" class="form-label">Cari Posisi</label>
                                <div class="input-group">
                                    <span class="input-group-text text-body"><i class="fas fa-search" aria-hidden="true"></i></span>
                                    <input
                                        type="text"
                                        id="search"
                                        name="search"
                                        class="form-control"
                                        value="{{ $search }}"
                                        placeholder="Cari nama, kode, departemen, tenant, atau deskripsi"
                                        data-testid="positions-search-input"
                                    >
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label for="tenant_id" class="form-label">Tenant</label>
                                <select name="tenant_id" id="tenant_id" class="form-control" data-testid="positions-tenant-filter">
                                    <option value="">Semua Tenant</option>
                                    @foreach ($tenants as $tenant)
                                        <option value="{{ $tenant->id }}" @selected($selectedTenantId === $tenant->id)>{{ $tenant->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="form-control" data-testid="positions-status-filter">
                                    <option value="">Semua Status</option>
                                    @foreach ($statuses as $statusValue => $statusLabel)
                                        <option value="{{ $statusValue }}" @selected($selectedStatus === $statusValue)>{{ $statusLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-4 col-md-12">
                                <div class="d-flex gap-2 justify-content-lg-end mt-lg-4">
                                    <button type="submit" class="btn bg-gradient-dark mb-0" data-testid="btn-apply-position-filter">
                                        <i class="fas fa-filter me-1"></i> Terapkan
                                    </button>
                                    <a href="{{ route('positions.index') }}" class="btn btn-light mb-0" data-testid="btn-reset-position-filter">
                                        Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                @if ($positions->isEmpty())
                    <div class="text-center py-5" data-testid="{{ $hasActivePositionFilters ? 'positions-filter-empty-state' : 'positions-empty-state' }}">
                        <i class="fas fa-briefcase fa-3x text-secondary mb-3"></i>
                        @if ($hasActivePositionFilters)
                            <p class="text-secondary mb-1">Tidak ada posisi yang cocok dengan pencarian atau filter saat ini.</p>
                            <p class="text-sm text-secondary mb-3">Coba ubah kata kunci, tenant, atau status untuk melihat hasil lain.</p>
                            <a href="{{ route('positions.index') }}" class="btn btn-light btn-sm mb-0">Reset Filter</a>
                        @else
                            <p class="text-secondary mb-0">Belum ada posisi, silakan tambah posisi baru</p>
                        @endif
                    </div>
                @else
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0" data-testid="positions-table">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4 text-start">Nama Posisi</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Kode</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Departemen</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Status</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Deskripsi</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($positions as $position)
                                    <tr>
                                        <td class="ps-4 text-start"><h6 class="mb-0 text-sm">{{ $position->name }}</h6></td>
                                        <td class="text-start"><span class="text-sm font-weight-bold">{{ $position->code ?? '—' }}</span></td>
                                        <td class="text-start"><span class="text-secondary text-sm">{{ $position->department?->name ?? '—' }}</span></td>
                                        <td class="text-center">
                                            <span class="badge {{ $position->status === 'active' ? 'bg-gradient-success' : 'bg-danger' }}" data-testid="position-status-{{ $position->id }}">
                                                {{ $position->status === 'active' ? 'Aktif' : 'Non-Aktif' }}
                                            </span>
                                        </td>
                                        <td class="text-start">
                                            <span class="text-secondary text-sm">{{ \Illuminate\Support\Str::limit($position->description ?? '—', 80) }}</span>
                                        </td>
                                        <td class="text-start">
                                            <div class="d-flex align-items-center gap-3">
                                                <a href="{{ route('positions.show', $position) }}" class="mx-1" data-bs-toggle="tooltip" title="Lihat" data-testid="btn-view-position-{{ $position->id }}">
                                                    <i class="fas fa-eye text-info"></i>
                                                </a>
                                                <a href="{{ route('positions.edit', $position) }}" class="mx-1" data-bs-toggle="tooltip" title="Edit" data-testid="btn-edit-position-{{ $position->id }}">
                                                    <i class="fas fa-edit text-secondary"></i>
                                                </a>
                                                <button type="button" class="border-0 bg-transparent p-0 mx-1" data-bs-toggle="modal" data-bs-target="#deletePositionIndexModal-{{ $position->id }}" title="Hapus" data-testid="btn-delete-position-{{ $position->id }}">
                                                    <i class="fas fa-trash text-danger"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <div class="modal fade" id="deletePositionIndexModal-{{ $position->id }}" tabindex="-1" aria-labelledby="deletePositionIndexModalLabel-{{ $position->id }}" aria-hidden="true" data-testid="position-index-delete-modal-{{ $position->id }}">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="deletePositionIndexModalLabel-{{ $position->id }}">Konfirmasi Hapus Posisi</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Apakah Anda yakin ingin menghapus posisi <strong>{{ $position->name }}</strong>?
                                                </div>
                                                <div class="modal-footer">
                                                    <form action="{{ route('positions.destroy', $position) }}" method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button" class="btn btn-light mb-0" data-bs-dismiss="modal">
                                                            <i class="fas fa-times me-1"></i> Batal
                                                        </button>
                                                        <button type="submit" class="btn btn-danger mb-0" data-testid="confirm-delete-position-{{ $position->id }}">
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
                        <p class="text-sm text-secondary mb-0">Menampilkan {{ $positions->count() }} dari total {{ $positions->total() }} posisi.</p>
                        {{ $positions->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="importPositionModal" tabindex="-1" aria-labelledby="importPositionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="importPositionModalLabel">Import Posisi dari Excel</h5>
                    <p class="text-sm text-secondary mb-0">Gunakan file Microsoft Excel `.xlsx` atau `.xls` dengan heading yang sesuai.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                @if (session('position_import_errors'))
                    <div class="alert alert-danger text-white" data-testid="position-import-errors">
                        <strong>Import gagal.</strong>
                        <ul class="mb-0 mt-2 ps-3">
                            @foreach (session('position_import_errors') as $positionImportError)
                                <li>{{ $positionImportError }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 border border-radius-lg p-3 bg-gray-100 mb-4">
                    <div>
                        <p class="text-sm font-weight-bold mb-1">Butuh format siap pakai?</p>
                        <p class="text-sm text-secondary mb-0">Unduh template Excel, isi datanya, lalu upload kembali di form import.</p>
                    </div>
                    <a href="{{ route('positions.import.template') }}" class="btn btn-outline-success btn-sm mb-0" data-testid="btn-download-positions-template">
                        <i class="fas fa-download me-1"></i> Download Template
                    </a>
                </div>

                <div class="border border-radius-lg p-3 bg-gray-100 mb-4" data-testid="position-import-template-info">
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
                                    <td><code>tenant_code</code></td>
                                    <td>Kode tenant yang sudah terdaftar.</td>
                                </tr>
                                <tr>
                                    <td><code>department_code</code></td>
                                    <td>Kode departemen pada tenant terkait.</td>
                                </tr>
                                <tr>
                                    <td><code>name</code></td>
                                    <td>Nama posisi. Dipakai sebagai kunci update jika sudah ada.</td>
                                </tr>
                                <tr>
                                    <td><code>code</code></td>
                                    <td>Kode internal posisi. Opsional tetapi harus unik per tenant.</td>
                                </tr>
                                <tr>
                                    <td><code>description</code></td>
                                    <td>Deskripsi singkat posisi.</td>
                                </tr>
                                <tr>
                                    <td><code>status</code></td>
                                    <td>Isi dengan <strong>active</strong>, <strong>inactive</strong>, <strong>aktif</strong>, atau <strong>nonaktif</strong>.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <form action="{{ route('positions.import') }}" method="POST" enctype="multipart/form-data" data-testid="positions-import-form">
                    @csrf
                    <div class="mb-3">
                        <label for="position_import_file" class="form-label">File Excel</label>
                        <input
                            type="file"
                            class="form-control @error('position_import_file', 'importPositions') is-invalid @enderror"
                            id="position_import_file"
                            name="position_import_file"
                            accept=".xlsx,.xls"
                        >
                        @error('position_import_file', 'importPositions')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light mb-0" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary mb-0">Import Sekarang</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addPositionIndexModal" tabindex="-1" aria-labelledby="addPositionIndexModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPositionIndexModalLabel">Tambah Posisi Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                @if ($tenants->isEmpty() || $departments->isEmpty())
                    <div class="border border-secondary border-radius-md p-4 text-center bg-gray-100" data-testid="positions-modal-empty-state">
                        <i class="fas fa-briefcase text-secondary fa-2x mb-3"></i>
                        <h6 class="mb-2">Belum ada tenant/departemen, silakan buat terlebih dahulu</h6>
                        <p class="text-sm text-secondary mb-0">Posisi membutuhkan tenant dan departemen agar struktur data organisasi tetap lengkap.</p>
                    </div>
                @else
                    <form action="{{ route('positions.store') }}" method="POST" data-testid="positions-index-create-form" autocomplete="off">
                        @csrf
                        <input type="text" class="d-none" tabindex="-1" autocomplete="username">
                        <input type="password" class="d-none" tabindex="-1" autocomplete="new-password">
                        @include('positions._form', ['forceBlankDefaults' => true, 'disableAutofill' => true])
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn btn-light mb-0" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i> Batal</button>
                            <button type="submit" class="btn btn-primary mb-0"><i class="fas fa-save me-1"></i> Simpan Posisi</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

@php($hasCreatePositionErrors = $errors->has('tenant_id') || $errors->has('department_id') || $errors->has('name') || $errors->has('code') || $errors->has('description') || $errors->has('status'))

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modalElement = document.getElementById('addPositionIndexModal');
            var formElement = modalElement ? modalElement.querySelector('[data-testid="positions-index-create-form"]') : null;
            var hasCreatePositionErrors = @json($hasCreatePositionErrors);

            if (!modalElement || !formElement) {
                return;
            }

            var resetCreateForm = function () {
                if (hasCreatePositionErrors) {
                    return;
                }

                formElement.reset();

                Array.from(formElement.querySelectorAll('input[type="text"], textarea')).forEach(function (field) {
                    field.value = '';
                });

                var tenantSelect = formElement.querySelector('[data-testid="position-tenant-select"]');
                var departmentSelect = formElement.querySelector('[data-testid="position-department-select"]');
                var statusSelect = formElement.querySelector('[data-testid="position-status-select"]');

                if (tenantSelect) {
                    tenantSelect.value = '';
                    tenantSelect.dispatchEvent(new Event('change'));
                }

                if (departmentSelect) {
                    departmentSelect.value = '';
                }

                if (statusSelect) {
                    statusSelect.value = 'active';
                }
            };

            modalElement.addEventListener('show.bs.modal', resetCreateForm);
            modalElement.addEventListener('shown.bs.modal', resetCreateForm);
        });
    </script>
@endpush

@if ($hasCreatePositionErrors)
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var modalElement = document.getElementById('addPositionIndexModal');

                if (modalElement && typeof bootstrap !== 'undefined') {
                    bootstrap.Modal.getOrCreateInstance(modalElement).show();
                }
            });
        </script>
    @endpush
@endif

@if (session('open_position_import_modal') || $errors->has('position_import_file', 'importPositions'))
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var modalElement = document.getElementById('importPositionModal');

                if (modalElement && typeof bootstrap !== 'undefined') {
                    bootstrap.Modal.getOrCreateInstance(modalElement).show();
                }
            });
        </script>
    @endpush
@endif

@endsection