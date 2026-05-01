@extends('layouts.user_type.auth')

@section('suppress-global-flash', '1')

@section('content')

@php($activeTenantFilters = collect([
    $search !== '' ? 'Pencarian: '.$search : null,
    $selectedStatus && isset($statuses[$selectedStatus]) ? 'Status: '.$statuses[$selectedStatus] : null,
])->filter()->values())
@php($hasActiveTenantFilters = $activeTenantFilters->isNotEmpty())
@php($canCreateTenant = ($summary['total'] ?? 0) === 0)

<div class="row">
    <div class="col-12">
        <x-flash-messages />

        <div class="card mb-4 mx-4 shadow-xs">
            <div class="card-header pb-0">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <h5 class="mb-1">Daftar Tenant</h5>
                        <p class="text-sm text-secondary mb-0">Kelola perusahaan atau cabang dengan ringkasan operasional, pencarian cepat, dan status tenant dalam satu halaman.</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap align-items-center">
                        @if ($hasActiveTenantFilters)
                            <span class="badge bg-gradient-info">Filter aktif</span>
                            @foreach ($activeTenantFilters as $activeTenantFilter)
                                <span class="badge bg-gradient-light text-dark">{{ $activeTenantFilter }}</span>
                            @endforeach
                        @endif
                        @if ($canCreateTenant)
                            <button type="button" class="btn bg-gradient-primary btn-sm mb-0" data-bs-toggle="modal" data-bs-target="#addTenantModal" data-testid="btn-add-tenant">
                                <i class="fas fa-plus me-1"></i> Tambah Tenant
                            </button>
                        @else
                            <span class="badge bg-gradient-secondary" data-testid="tenant-limit-badge">Maksimum 1 tenant</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card-body px-0 pt-0 pb-2">
                <div class="px-4 pt-4">
                    <div class="row g-3 mb-4">
                        <div class="col-xl-4 col-md-6">
                            <div class="card border shadow-xs h-100" data-testid="tenants-summary-total">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Total Tenant</p>
                                    <h5 class="mb-0">{{ $summary['total'] }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <div class="card border shadow-xs h-100" data-testid="tenants-summary-active">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Tenant Aktif</p>
                                    <h5 class="mb-0 text-success">{{ $summary['active'] }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-12">
                            <div class="card border shadow-xs h-100" data-testid="tenants-summary-inactive">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Tenant Tidak Aktif</p>
                                    <h5 class="mb-0 text-secondary">{{ $summary['inactive'] }}</h5>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border border-radius-xl p-3 mb-4 bg-gray-100">
                        <form action="{{ route('tenants.index') }}" method="GET" class="row g-3 align-items-end" data-testid="tenants-filter-form">
                            <div class="col-lg-7 col-md-6">
                                <label for="search" class="form-label">Cari Tenant</label>
                                <div class="input-group">
                                    <span class="input-group-text text-body"><i class="fas fa-search" aria-hidden="true"></i></span>
                                    <input type="text" id="search" name="search" class="form-control" value="{{ $search }}" placeholder="Cari nama, kode, domain, alamat, kontak, atau deskripsi" data-testid="tenants-search-input">
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="form-control" data-testid="tenants-status-filter">
                                    <option value="">Semua Status</option>
                                    @foreach ($statuses as $statusValue => $statusLabel)
                                        <option value="{{ $statusValue }}" @selected($selectedStatus === $statusValue)>{{ $statusLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-12">
                                <div class="d-flex gap-2 justify-content-lg-end mt-lg-4">
                                    <button type="submit" class="btn bg-gradient-dark mb-0" data-testid="btn-apply-tenant-filter">
                                        <i class="fas fa-filter me-1"></i> Terapkan
                                    </button>
                                    <a href="{{ route('tenants.index') }}" class="btn btn-light mb-0" data-testid="btn-reset-tenant-filter">
                                        Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                @if ($tenants->isEmpty())
                    <div class="text-center py-5" data-testid="{{ $hasActiveTenantFilters ? 'tenants-filter-empty-state' : 'tenant-empty-state' }}">
                        <i class="fas fa-building fa-3x text-secondary mb-3"></i>
                        @if ($hasActiveTenantFilters)
                            <p class="text-secondary mb-1">Tidak ada tenant yang cocok dengan pencarian atau filter saat ini.</p>
                            <p class="text-sm text-secondary mb-3">Coba ubah kata kunci atau status untuk melihat hasil lain.</p>
                            <a href="{{ route('tenants.index') }}" class="btn btn-light btn-sm mb-0">Reset Filter</a>
                        @else
                            <p class="text-secondary mb-0">Belum ada tenant, silakan tambah terlebih dahulu</p>
                        @endif
                    </div>
                @else
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0" data-testid="tenants-table">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4 text-start">Nama Tenant</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Kode</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Status</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Alamat</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Kontak</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Ringkasan</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($tenants as $tenant)
                                    <tr>
                                        <td class="ps-4 text-start">
                                            @php($tenantBrandingPath = $tenant->branding_path)
                                            <div class="d-flex align-items-center gap-3">
                                                @if($tenantBrandingPath)
                                                    <img src="{{ asset($tenantBrandingPath) }}" alt="Logo {{ $tenant->name }}" height="36" width="36" class="border rounded bg-white p-1" data-testid="tenant-branding-thumb-{{ $tenant->id }}">
                                                @else
                                                    <div class="d-inline-flex align-items-center justify-content-center border rounded bg-gray-100 text-secondary text-xs fw-bold" style="height:36px;width:36px;" data-testid="tenant-branding-thumb-placeholder-{{ $tenant->id }}">N/A</div>
                                                @endif
                                                <div class="d-flex flex-column">
                                                    <h6 class="mb-0 text-sm">{{ $tenant->name }}</h6>
                                                    <span class="text-sm text-secondary">{{ $tenant->domain }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-start">
                                            <span class="text-sm font-weight-bold">{{ $tenant->code ?? '—' }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $tenant->status === 'active' ? 'bg-gradient-success' : 'bg-gradient-secondary' }}" data-testid="tenant-status-{{ $tenant->id }}">
                                                {{ $statuses[$tenant->status] ?? ucfirst((string) $tenant->status) }}
                                            </span>
                                        </td>
                                        <td class="text-start">
                                            <span class="text-secondary text-sm">{{ $tenant->address ?? '—' }}</span>
                                        </td>
                                        <td class="text-start">
                                            <span class="text-secondary text-sm">{{ $tenant->contact ?? '—' }}</span>
                                        </td>
                                        <td class="text-start">
                                            <div class="d-flex flex-wrap gap-2">
                                                <span class="badge bg-info" data-testid="tenant-users-count-{{ $tenant->id }}">{{ $tenant->users_count }} User</span>
                                                <span class="badge bg-success" data-testid="tenant-employees-count-{{ $tenant->id }}">{{ $tenant->employees_count }} Karyawan</span>
                                                <span class="badge bg-warning text-dark" data-testid="tenant-departments-count-{{ $tenant->id }}">{{ $tenant->departments_count }} Departemen</span>
                                            </div>
                                        </td>
                                        <td class="text-start">
                                            <div class="d-flex align-items-center gap-3">
                                                <a href="{{ route('tenants.show', $tenant) }}" class="mx-1" data-bs-toggle="tooltip" title="Lihat" data-testid="btn-view-tenant-{{ $tenant->id }}">
                                                    <i class="fas fa-eye text-info"></i>
                                                </a>
                                                <a href="{{ route('tenants.edit', $tenant) }}" class="mx-1" data-bs-toggle="tooltip" title="Edit" data-testid="btn-edit-tenant-{{ $tenant->id }}">
                                                    <i class="fas fa-edit text-secondary"></i>
                                                </a>
                                                <button type="button" class="border-0 bg-transparent p-0 mx-1" data-testid="btn-delete-tenant-{{ $tenant->id }}" data-bs-toggle="modal" data-bs-target="#deleteTenantModal{{ $tenant->id }}" title="Hapus">
                                                    <i class="fas fa-trash text-danger"></i>
                                                </button>
                                            </div>

                                            <div class="modal fade" id="deleteTenantModal{{ $tenant->id }}" tabindex="-1" aria-labelledby="deleteTenantLabel{{ $tenant->id }}" aria-hidden="true" data-testid="tenant-index-delete-modal-{{ $tenant->id }}">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="deleteTenantLabel{{ $tenant->id }}">Konfirmasi Hapus Tenant</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            Apakah Anda yakin ingin menghapus tenant <strong>{{ $tenant->name }}</strong>?
                                                            <div class="text-sm text-secondary mt-2">Tenant yang masih memiliki user terdaftar tidak dapat dihapus.</div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-light mb-0" data-bs-dismiss="modal">Batal</button>
                                                            <form action="{{ route('tenants.destroy', $tenant) }}" method="POST" class="d-inline" data-testid="confirm-delete-tenant-form-{{ $tenant->id }}">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-danger mb-0">Hapus</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="px-4 pt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <p class="text-sm text-secondary mb-0">Menampilkan {{ $tenants->count() }} dari total {{ $tenants->total() }} tenant.</p>
                        {{ $tenants->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if ($canCreateTenant)
    <div class="modal fade" id="addTenantModal" tabindex="-1" aria-labelledby="addTenantModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTenantModalLabel"><i class="fas fa-building me-2 text-primary"></i>Tambah Tenant</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <form action="{{ route('tenants.store') }}" method="POST" enctype="multipart/form-data" data-testid="tenant-create-form">
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Tenant <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Domain <span class="text-danger">*</span></label>
                                <input type="text" name="domain" class="form-control @error('domain') is-invalid @enderror" value="{{ old('domain') }}" required placeholder="contoh-tenant.test">
                                @error('domain')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-control @error('status') is-invalid @enderror">
                                    <option value="active" @selected(old('status', 'active') === 'active')>Aktif</option>
                                    <option value="inactive" @selected(old('status') === 'inactive')>Tidak Aktif</option>
                                </select>
                                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Deskripsi</label>
                                <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror" placeholder="Tenant contoh untuk uji regression">{{ old('description') }}</textarea>
                                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Logo / Favicon Tenant</label>
                                <input type="file" name="branding" class="form-control @error('branding') is-invalid @enderror" accept="image/*,.ico">
                                @error('branding')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn bg-gradient-primary"><i class="fas fa-save me-1"></i> Simpan Tenant</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if ($errors->has('name') || $errors->has('domain') || $errors->has('status') || $errors->has('description') || $errors->has('branding'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var modalElement = document.getElementById('addTenantModal');
                if (modalElement && typeof bootstrap !== 'undefined') {
                    bootstrap.Modal.getOrCreateInstance(modalElement).show();
                }
            });
        </script>
    @endif
@endif

@endsection