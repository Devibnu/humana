@extends('layouts.user_type.auth')

@section('content')

@php($activeRoleFilters = collect([
    $search !== '' ? 'Pencarian: '.$search : null,
    $selectedUsage && isset($usageOptions[$selectedUsage]) ? 'Penggunaan: '.$usageOptions[$selectedUsage] : null,
])->filter()->values())
@php($hasActiveRoleFilters = $activeRoleFilters->isNotEmpty())

<div class="row">
    <div class="col-12">
        <x-flash-messages />

        <div class="card mx-4 mb-4 shadow-xs">
            <div class="card-header pb-0">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <h5 class="mb-1">Daftar Role</h5>
                        <p class="text-sm text-secondary mb-0">Kelola peran pengguna dan hak akses menu HRIS dengan tampilan yang lebih mudah dipindai dan difilter.</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap align-items-center">
                        @if ($hasActiveRoleFilters)
                            <span class="badge bg-gradient-info">Filter aktif</span>
                            @foreach ($activeRoleFilters as $activeRoleFilter)
                                <span class="badge bg-gradient-light text-dark">{{ $activeRoleFilter }}</span>
                            @endforeach
                        @endif
                        <a href="{{ route('roles.create') }}" class="btn bg-gradient-primary btn-sm mb-0" data-testid="btn-add-role">
                            <i class="fas fa-plus me-1"></i> Tambah Role
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body px-0 pt-0 pb-2">
                <div class="px-4 pt-4">
                    <div class="row g-3 mb-4">
                        <div class="col-xl-4 col-md-6">
                            <div class="card border shadow-xs h-100" data-testid="roles-summary-total">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Total Role</p>
                                    <h5 class="mb-0">{{ $summary['total'] }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <div class="card border shadow-xs h-100" data-testid="roles-summary-assigned">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Role Dipakai User</p>
                                    <h5 class="mb-0 text-success">{{ $summary['assigned'] }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-12">
                            <div class="card border shadow-xs h-100" data-testid="roles-summary-permissions">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Total Hak Akses</p>
                                    <h5 class="mb-0 text-info">{{ $summary['permissions'] }}</h5>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border border-radius-xl p-3 mb-4 bg-gray-100">
                        <form action="{{ route('roles.index') }}" method="GET" class="row g-3 align-items-end" data-testid="roles-filter-form">
                            <div class="col-lg-7 col-md-6">
                                <label for="search" class="form-label">Cari Role</label>
                                <div class="input-group">
                                    <span class="input-group-text text-body"><i class="fas fa-search" aria-hidden="true"></i></span>
                                    <input type="text" id="search" name="search" class="form-control" value="{{ $search }}" placeholder="Cari nama role atau deskripsinya" data-testid="roles-search-input">
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label for="usage" class="form-label">Penggunaan</label>
                                <select name="usage" id="usage" class="form-control" data-testid="roles-usage-filter">
                                    <option value="">Semua Role</option>
                                    @foreach ($usageOptions as $usageValue => $usageLabel)
                                        <option value="{{ $usageValue }}" @selected($selectedUsage === $usageValue)>{{ $usageLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-12">
                                <div class="d-flex gap-2 justify-content-lg-end mt-lg-4">
                                    <button type="submit" class="btn bg-gradient-dark mb-0" data-testid="btn-apply-role-filter">
                                        <i class="fas fa-filter me-1"></i> Terapkan
                                    </button>
                                    <a href="{{ route('roles.index') }}" class="btn btn-light mb-0" data-testid="btn-reset-role-filter">
                                        Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                @if ($roles->isEmpty())
                    <div class="text-center py-5" data-testid="{{ $hasActiveRoleFilters ? 'roles-filter-empty-state' : 'roles-empty-state' }}">
                        <i class="fas fa-user-shield fa-3x text-secondary mb-3"></i>
                        @if ($hasActiveRoleFilters)
                            <p class="text-secondary mb-1">Tidak ada role yang cocok dengan pencarian atau filter saat ini.</p>
                            <p class="text-sm text-secondary mb-3">Coba ubah kata kunci atau filter penggunaan untuk melihat hasil lain.</p>
                            <a href="{{ route('roles.index') }}" class="btn btn-light btn-sm mb-0">Reset Filter</a>
                        @else
                            <p class="text-secondary mb-0">Belum ada role yang tersedia.</p>
                        @endif
                    </div>
                @else
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0" data-testid="roles-table">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4 text-start">Role</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Deskripsi</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Hak Akses</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Dipakai User</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($roles as $role)
                                    <tr>
                                        <td class="ps-4 text-start">
                                            <div class="d-flex flex-column">
                                                <h6 class="mb-0 text-sm">{{ strtoupper($role->name) }}</h6>
                                                <span class="text-sm text-secondary">Key: {{ $role->system_key }}</span>
                                            </div>
                                        </td>
                                        <td class="text-start">
                                            <p class="text-sm text-secondary mb-0">{{ $role->description ?: 'Belum ada deskripsi role.' }}</p>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-gradient-info" data-testid="role-permissions-count-{{ $role->id }}">{{ $role->permissions_count }} menu</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $role->users_count > 0 ? 'bg-gradient-success' : 'bg-gradient-secondary' }}" data-testid="role-users-count-{{ $role->id }}">{{ $role->users_count }} user</span>
                                        </td>
                                        <td class="text-start">
                                            <div class="d-flex align-items-center gap-3">
                                                <a href="{{ route('roles.edit', $role) }}" class="mx-1" title="Edit" data-bs-toggle="tooltip" data-testid="btn-edit-role-{{ $role->id }}">
                                                    <i class="fas fa-edit text-secondary"></i>
                                                </a>
                                                <button type="button" class="border-0 bg-transparent p-0 mx-1" data-bs-toggle="modal" data-bs-target="#deleteRoleModal-{{ $role->id }}" title="Hapus" data-testid="btn-delete-role-{{ $role->id }}">
                                                    <i class="fas fa-trash text-danger"></i>
                                                </button>
                                            </div>

                                            <div class="modal fade" id="deleteRoleModal-{{ $role->id }}" tabindex="-1" aria-labelledby="deleteRoleModalLabel-{{ $role->id }}" aria-hidden="true" data-testid="role-index-delete-modal-{{ $role->id }}">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="deleteRoleModalLabel-{{ $role->id }}">Konfirmasi Hapus Role</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                                        </div>
                                                        <div class="modal-body text-start">
                                                            Anda yakin ingin menghapus role <strong>{{ $role->name }}</strong>? Hak akses yang terkait juga akan dihapus.
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-light mb-0" data-bs-dismiss="modal">Batal</button>
                                                            <form action="{{ route('roles.destroy', $role) }}" method="POST" class="d-inline" data-testid="confirm-delete-role-form-{{ $role->id }}">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn bg-gradient-danger mb-0">Hapus Role</button>
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
                        <p class="text-sm text-secondary mb-0">Menampilkan {{ $roles->count() }} dari total {{ $roles->total() }} role.</p>
                        {{ $roles->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection