@extends('layouts.user_type.auth')

@section('content')

@php($activeWorkLocationFilters = collect([
    $search !== '' ? 'Pencarian: '.$search : null,
    $selectedTenantName ? 'Tenant: '.$selectedTenantName : null,
])->filter()->values())
@php($hasActiveWorkLocationFilters = $activeWorkLocationFilters->isNotEmpty())

<div class="row">
    <div class="col-12">
        <x-flash-messages />
        <div class="card mb-4 mx-4 shadow-xs">
            <div class="card-header pb-0">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <h5 class="mb-1">Daftar Lokasi Kerja</h5>
                        <p class="text-sm text-secondary mb-0">Kelola titik kerja, radius absensi, dan cakupan tenant dalam satu tampilan yang lebih rapi dan mudah dipindai.</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap align-items-center">
                        @if ($hasActiveWorkLocationFilters)
                            <span class="badge bg-gradient-info">Filter aktif</span>
                            @foreach ($activeWorkLocationFilters as $activeWorkLocationFilter)
                                <span class="badge bg-gradient-light text-dark">{{ $activeWorkLocationFilter }}</span>
                            @endforeach
                        @endif
                        <a href="{{ route('work_locations.create') }}" class="btn bg-gradient-primary btn-sm mb-0" data-testid="btn-open-create-work-location">
                            <i class="fas fa-plus me-1"></i> Tambah Lokasi Kerja
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <div class="px-4 pt-4">
                    <div class="row g-3 mb-4">
                        <div class="col-xl-4 col-md-6">
                            <div class="card border shadow-xs h-100" data-testid="work-locations-summary-total">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Total Lokasi Kerja</p>
                                    <h5 class="mb-0">{{ $summary['total'] }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <div class="card border shadow-xs h-100" data-testid="work-locations-summary-tenant-count">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Tenant Terjangkau</p>
                                    <h5 class="mb-0 text-info">{{ $summary['tenant_count'] }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-12">
                            <div class="card border shadow-xs h-100" data-testid="work-locations-summary-average-radius">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Rata-rata Radius</p>
                                    <h5 class="mb-0 text-secondary">{{ number_format($summary['average_radius'], 0, ',', '.') }} m</h5>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border border-radius-xl p-3 mb-4 bg-gray-100">
                        <form action="{{ route('work_locations.index') }}" method="GET" class="row g-3 align-items-end" data-testid="work-locations-filter-form">
                            <div class="col-lg-7 col-md-6">
                                <label for="search" class="form-label">Cari Lokasi Kerja</label>
                                <div class="input-group">
                                    <span class="input-group-text text-body"><i class="fas fa-search" aria-hidden="true"></i></span>
                                    <input
                                        type="text"
                                        id="search"
                                        name="search"
                                        class="form-control"
                                        value="{{ $search }}"
                                        placeholder="Cari nama, alamat, tenant, koordinat, atau radius"
                                        data-testid="work-locations-search-input"
                                    >
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label for="tenant_id" class="form-label">Tenant</label>
                                <select name="tenant_id" id="tenant_id" class="form-control" data-testid="work-locations-tenant-filter">
                                    <option value="">Semua Tenant</option>
                                    @foreach ($tenants as $tenant)
                                        <option value="{{ $tenant->id }}" @selected($selectedTenantId === $tenant->id)>{{ $tenant->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-12">
                                <div class="d-flex gap-2 justify-content-lg-end mt-lg-4">
                                    <button type="submit" class="btn bg-gradient-dark mb-0" data-testid="btn-apply-work-location-filter">
                                        <i class="fas fa-filter me-1"></i> Terapkan
                                    </button>
                                    <a href="{{ route('work_locations.index') }}" class="btn btn-light mb-0" data-testid="btn-reset-work-location-filter">
                                        Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                @if ($workLocations->isEmpty())
                    <div class="text-center py-5" data-testid="{{ $hasActiveWorkLocationFilters ? 'work-locations-filter-empty-state' : 'work-locations-empty-state' }}">
                        <i class="fas fa-map-marker-alt fa-3x text-secondary mb-3"></i>
                        @if ($hasActiveWorkLocationFilters)
                            <p class="text-secondary mb-1">Tidak ada lokasi kerja yang cocok dengan pencarian atau filter saat ini.</p>
                            <p class="text-sm text-secondary mb-3">Coba ubah kata kunci atau tenant untuk melihat hasil lain.</p>
                            <a href="{{ route('work_locations.index') }}" class="btn btn-light btn-sm mb-0">Reset Filter</a>
                        @else
                            <p class="text-secondary mb-0">Belum ada lokasi kerja, silakan tambah lokasi kerja baru.</p>
                        @endif
                    </div>
                @else
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0" data-testid="work-locations-table">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4 text-start">Nama Lokasi</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Alamat</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Koordinat</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Radius</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Tenant</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Karyawan</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($workLocations as $workLocation)
                                    <tr>
                                        <td class="ps-4 text-start"><h6 class="mb-0 text-sm">{{ $workLocation->name }}</h6></td>
                                        <td class="text-start"><span class="text-secondary text-sm">{{ $workLocation->address ?: '—' }}</span></td>
                                        <td class="text-start"><span class="text-secondary text-sm">{{ $workLocation->latitude }}, {{ $workLocation->longitude }}</span></td>
                                        <td class="text-center"><span class="badge bg-gradient-info">{{ number_format($workLocation->radius, 0, ',', '.') }} m</span></td>
                                        <td class="text-start"><span class="text-secondary text-sm">{{ $workLocation->tenant?->name ?? '—' }}</span></td>
                                        <td class="text-start">
                                            <span class="badge bg-success" data-testid="work-location-employees-count-{{ $workLocation->id }}">{{ $workLocation->employees_count }} Karyawan</span>
                                        </td>
                                        <td class="text-start">
                                            <div class="d-flex align-items-center gap-3">
                                                <a href="{{ route('work_locations.edit', $workLocation) }}" class="mx-1" data-bs-toggle="tooltip" title="Edit" data-testid="btn-edit-work-location-{{ $workLocation->id }}">
                                                    <i class="fas fa-edit text-secondary"></i>
                                                </a>
                                                <button type="button" class="border-0 bg-transparent p-0 mx-1" data-bs-toggle="modal" data-bs-target="#deleteWorkLocationIndexModal-{{ $workLocation->id }}" title="Hapus" data-testid="btn-delete-work-location-{{ $workLocation->id }}">
                                                    <i class="fas fa-trash text-danger"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <div class="modal fade" id="deleteWorkLocationIndexModal-{{ $workLocation->id }}" tabindex="-1" aria-labelledby="deleteWorkLocationIndexModalLabel-{{ $workLocation->id }}" aria-hidden="true" data-testid="work-location-index-delete-modal-{{ $workLocation->id }}">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="deleteWorkLocationIndexModalLabel-{{ $workLocation->id }}">Konfirmasi Hapus Lokasi Kerja</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Apakah Anda yakin ingin menghapus lokasi kerja <strong>{{ $workLocation->name }}</strong>?
                                                </div>
                                                <div class="modal-footer">
                                                    <form action="{{ route('work_locations.destroy', $workLocation) }}" method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button" class="btn btn-light mb-0" data-bs-dismiss="modal">
                                                            <i class="fas fa-times me-1"></i> Batal
                                                        </button>
                                                        <button type="submit" class="btn btn-danger mb-0" data-testid="confirm-delete-work-location-{{ $workLocation->id }}">
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
                        <p class="text-sm text-secondary mb-0">Menampilkan {{ $workLocations->count() }} dari total {{ $workLocations->total() }} lokasi kerja.</p>
                        {{ $workLocations->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
