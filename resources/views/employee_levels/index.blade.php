@extends('layouts.user_type.auth')

@section('content')

@php($activeLevelFilters = collect([
    $search !== '' ? 'Pencarian: '.$search : null,
    $selectedTenantName ? 'Tenant: '.$selectedTenantName : null,
    $selectedStatus && isset($statuses[$selectedStatus]) ? 'Status: '.$statuses[$selectedStatus] : null,
])->filter()->values())
@php($hasActiveLevelFilters = $activeLevelFilters->isNotEmpty())

<div class="row">
    <div class="col-12">
        <x-flash-messages />

        @if ($errors->any())
            <div class="alert alert-danger text-white mx-4">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="card mb-4 mx-4 shadow-xs">
            <div class="card-header pb-0">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <h5 class="mb-1">Level Karyawan</h5>
                        <p class="text-sm text-secondary mb-0">Kelola master level karyawan yang dipakai di form karyawan.</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap align-items-center justify-content-end">
                        @if ($hasActiveLevelFilters)
                            <span class="badge bg-gradient-info">Filter aktif</span>
                            @foreach ($activeLevelFilters as $activeLevelFilter)
                                <span class="badge bg-gradient-light text-dark">{{ $activeLevelFilter }}</span>
                            @endforeach
                        @endif
                        <button type="button" class="btn bg-gradient-primary btn-sm mb-0" data-bs-toggle="modal" data-bs-target="#addEmployeeLevelModal" data-testid="btn-add-employee-level">
                            <i class="fas fa-plus me-1"></i> Tambah Level
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <div class="px-4 pt-4">
                    <div class="row g-3 mb-4">
                        <div class="col-xl-4 col-md-6">
                            <div class="card border shadow-xs h-100">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Total Level</p>
                                    <h5 class="mb-0">{{ $summary['total'] }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <div class="card border shadow-xs h-100">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Level Aktif</p>
                                    <h5 class="mb-0 text-success">{{ $summary['active'] }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-12">
                            <div class="card border shadow-xs h-100">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Level Nonaktif</p>
                                    <h5 class="mb-0 text-secondary">{{ $summary['inactive'] }}</h5>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border border-radius-xl p-3 mb-4 bg-gray-100">
                        <form action="{{ route('employee-levels.index') }}" method="GET" class="row g-3 align-items-end" data-testid="employee-levels-filter-form">
                            <div class="col-lg-4 col-md-6">
                                <label for="search" class="form-label">Cari Level</label>
                                <div class="input-group">
                                    <span class="input-group-text text-body"><i class="fas fa-search" aria-hidden="true"></i></span>
                                    <input type="text" id="search" name="search" class="form-control" value="{{ $search }}" placeholder="Cari nama, kode, atau deskripsi">
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label for="tenant_id" class="form-label">Tenant</label>
                                <select name="tenant_id" id="tenant_id" class="form-control">
                                    <option value="">Semua Tenant</option>
                                    @foreach ($tenants as $tenant)
                                        <option value="{{ $tenant->id }}" @selected($selectedTenantId === $tenant->id)>{{ $tenant->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="form-control">
                                    <option value="">Semua Status</option>
                                    @foreach ($statuses as $statusValue => $statusLabel)
                                        <option value="{{ $statusValue }}" @selected($selectedStatus === $statusValue)>{{ $statusLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-4 col-md-12">
                                <div class="d-flex gap-2 justify-content-lg-end mt-lg-4">
                                    <button type="submit" class="btn bg-gradient-dark mb-0">
                                        <i class="fas fa-filter me-1"></i> Terapkan
                                    </button>
                                    <a href="{{ route('employee-levels.index') }}" class="btn btn-light mb-0">Reset</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                @if ($employeeLevels->isEmpty())
                    <div class="text-center py-5" data-testid="employee-levels-empty-state">
                        <i class="fas fa-layer-group fa-3x text-secondary mb-3"></i>
                        <p class="text-secondary mb-0">Belum ada level karyawan.</p>
                    </div>
                @else
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0" data-testid="employee-levels-table">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4 text-start">Nama Level</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Kode</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Tenant</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Status</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Urutan</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Dipakai</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($employeeLevels as $level)
                                    <tr>
                                        <td class="ps-4 text-start">
                                            <h6 class="mb-0 text-sm">{{ $level->name }}</h6>
                                            <span class="text-secondary text-xs">{{ \Illuminate\Support\Str::limit($level->description ?? '—', 70) }}</span>
                                        </td>
                                        <td class="text-start"><span class="text-sm font-weight-bold">{{ $level->code }}</span></td>
                                        <td class="text-start"><span class="text-secondary text-sm">{{ $level->tenant?->name ?? '—' }}</span></td>
                                        <td class="text-center">
                                            <span class="badge {{ $level->status === 'active' ? 'bg-gradient-success' : 'bg-gradient-secondary' }}">{{ $level->status === 'active' ? 'Aktif' : 'Nonaktif' }}</span>
                                        </td>
                                        <td class="text-start"><span class="text-secondary text-sm">{{ $level->sort_order }}</span></td>
                                        <td class="text-start"><span class="badge bg-info">{{ $level->employees_count }} Karyawan</span></td>
                                        <td class="text-start">
                                            <div class="d-flex align-items-center gap-3">
                                                <a href="{{ route('employee-levels.edit', $level) }}" class="mx-1" data-bs-toggle="tooltip" title="Edit level" data-testid="btn-edit-employee-level-{{ $level->id }}">
                                                    <i class="fas fa-edit text-secondary"></i>
                                                </a>
                                                <button type="button" class="border-0 bg-transparent p-0 mx-1" data-bs-toggle="modal" data-bs-target="#deleteEmployeeLevelModal-{{ $level->id }}" title="Hapus level" data-testid="btn-delete-employee-level-{{ $level->id }}">
                                                    <i class="fas fa-trash text-danger"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <div class="modal fade" id="deleteEmployeeLevelModal-{{ $level->id }}" tabindex="-1" aria-labelledby="deleteEmployeeLevelModalLabel-{{ $level->id }}" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="deleteEmployeeLevelModalLabel-{{ $level->id }}">Konfirmasi Hapus Level</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Apakah Anda yakin ingin menghapus level <strong>{{ $level->name }}</strong>?
                                                </div>
                                                <div class="modal-footer">
                                                    <form action="{{ route('employee-levels.destroy', $level) }}" method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button" class="btn btn-light mb-0" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-danger mb-0">Hapus</button>
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
                        <p class="text-sm text-secondary mb-0">Menampilkan {{ $employeeLevels->count() }} dari total {{ $employeeLevels->total() }} level.</p>
                        {{ $employeeLevels->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addEmployeeLevelModal" tabindex="-1" aria-labelledby="addEmployeeLevelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="addEmployeeLevelModalLabel">Tambah Level Karyawan</h5>
                    <p class="text-sm text-secondary mb-0">Level ini akan muncul di dropdown form karyawan.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form action="{{ route('employee-levels.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    @include('employee_levels._form')
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light mb-0" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn bg-gradient-primary mb-0">
                        <i class="fas fa-save me-1"></i> Simpan Level
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
