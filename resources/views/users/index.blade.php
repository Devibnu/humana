@extends('layouts.user_type.auth')

@section('suppress-global-flash', '1')

@section('content')

@php($currentUser = auth()->user())
@php($activeUserFilters = collect([
    $selectedTenantName ? 'Tenant: '.$selectedTenantName : null,
    $selectedRole && isset($roles[$selectedRole]) ? 'Role: '.$roles[$selectedRole] : null,
    $selectedLinked ? 'Linked: '.($selectedLinked === 'only' ? 'Linked only' : 'Unlinked only') : null,
])->filter()->values())
@php($hasActiveUserFilters = $activeUserFilters->isNotEmpty())
@php($totalUserCount = $linkedEmployeeCount + $unlinkedEmployeeCount)

<div class="row">
    <div class="col-12">
        <x-flash-messages />

        <div class="card mb-4 mx-4 shadow-xs">
            <div class="card-header pb-0">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <h5 class="mb-1">Daftar Pengguna</h5>
                        <p class="text-sm text-secondary mb-0">Kelola akun pengguna berdasarkan tenant, role, status koneksi karyawan, dan akses sistem.</p>
                        @if ($currentUser && $currentUser->isManager())
                            <p class="text-xs text-secondary mb-0 mt-2">
                                Tenant scope active: you are only viewing users from {{ $currentUser->tenant?->name ?? 'your tenant' }}.
                            </p>
                        @endif
                    </div>
                    <div class="d-flex gap-2 flex-wrap align-items-center justify-content-end">
                        @if ($hasActiveUserFilters)
                            <span class="badge bg-gradient-info">Filter aktif</span>
                            @foreach ($activeUserFilters as $activeUserFilter)
                                <span class="badge bg-gradient-light text-dark">{{ $activeUserFilter }}</span>
                            @endforeach
                        @endif
                        <a href="{{ route('users.export', array_merge(request()->query(), ['format' => 'csv'])) }}" class="btn btn-outline-dark btn-sm mb-0" data-testid="btn-export-users-csv">
                            <i class="fas fa-file-csv me-1"></i> Export CSV
                        </a>
                        <a href="{{ route('users.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}" class="btn btn-outline-success btn-sm mb-0" data-testid="btn-export-users-xlsx">
                            <i class="fas fa-file-excel me-1"></i> Export Excel
                        </a>
                        @if ($currentUser && $currentUser->isAdminHr())
                            <a href="{{ route('users.create') }}" class="btn bg-gradient-primary btn-sm mb-0" data-testid="btn-add-user">
                                <i class="fas fa-plus me-1"></i> Tambah Pengguna
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card-body px-0 pt-0 pb-2">
                <div class="px-4 pt-4">
                    <div class="row g-3 mb-4">
                        <div class="col-xl-4 col-md-6">
                            <div class="card border shadow-xs h-100" data-testid="users-summary-total">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Total Pengguna</p>
                                    <h5 class="mb-0">{{ $totalUserCount }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <div class="card border shadow-xs h-100" data-testid="users-summary-linked">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Employee Linked</p>
                                    <h5 class="mb-0 text-success">{{ $linkedEmployeeCount }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-12">
                            <div class="card border shadow-xs h-100" data-testid="users-summary-unlinked">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Employee Unlinked</p>
                                    <h5 class="mb-0 text-secondary">{{ $unlinkedEmployeeCount }}</h5>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border border-radius-xl p-3 mb-4 bg-gray-100">
                        <form action="{{ route('users.index') }}" method="GET" class="row g-3 align-items-end" data-testid="users-filter-form">
                            @if ($currentUser && $currentUser->isAdminHr())
                                <div class="col-lg-3 col-md-6">
                                    <label for="tenant_id" class="form-label">Tenant</label>
                                    <select name="tenant_id" id="tenant_id" class="form-control" data-testid="users-tenant-filter">
                                        <option value="">Semua Tenant</option>
                                        @foreach ($tenants as $tenant)
                                            <option value="{{ $tenant->id }}" @selected($selectedTenantId === $tenant->id)>
                                                {{ $tenant->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="col-lg-3 col-md-6">
                                <label for="role" class="form-label">Role</label>
                                <select name="role" id="role" class="form-control" data-testid="users-role-filter">
                                    <option value="" @selected($selectedRole === null)>Semua Role</option>
                                    @foreach ($roles as $roleValue => $roleLabel)
                                        <option value="{{ $roleValue }}" @selected($selectedRole === $roleValue)>{{ $roleLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <label for="linked" class="form-label">Koneksi Karyawan</label>
                                <select name="linked" id="linked" class="form-control" data-testid="users-linked-filter">
                                    <option value="" @selected($selectedLinked === null)>Semua Pengguna</option>
                                    <option value="only" @selected($selectedLinked === 'only')>Linked only</option>
                                    <option value="unlinked" @selected($selectedLinked === 'unlinked')>Unlinked only</option>
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <div class="d-flex gap-2 justify-content-lg-end mt-lg-4">
                                    <button type="submit" class="btn bg-gradient-dark mb-0" data-testid="btn-apply-users-filter">
                                        <i class="fas fa-filter me-1"></i> Terapkan
                                    </button>
                                    <a href="{{ route('users.index') }}" class="btn btn-light mb-0" data-testid="btn-reset-users-filter">
                                        Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                @if ($users->isEmpty())
                    <div class="text-center py-5" data-testid="{{ $hasActiveUserFilters ? 'users-filter-empty-state' : 'users-empty-state' }}">
                        <i class="fas fa-users-slash fa-3x text-secondary mb-3"></i>
                        @if ($hasActiveUserFilters)
                            <p class="text-secondary mb-1">Tidak ada pengguna yang cocok dengan filter saat ini.</p>
                            <p class="text-sm text-secondary mb-3">Coba ubah tenant, role, atau status koneksi karyawan untuk melihat hasil lain.</p>
                            <a href="{{ route('users.index') }}" class="btn btn-light btn-sm mb-0">Reset Filter</a>
                        @else
                            <p class="text-secondary mb-0">Belum ada pengguna, silakan tambah terlebih dahulu</p>
                        @endif
                    </div>
                @else
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0" data-testid="users-table">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4 text-start">Nama Pengguna</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Email</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Tenant</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Linked Employee</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Role</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">
                                        <span class="d-inline-flex align-items-center gap-1">
                                            Status
                                            <i class="fas fa-info-circle text-secondary text-xs" data-bs-toggle="tooltip" title="Status menunjukkan apakah akun dapat digunakan untuk login."></i>
                                        </span>
                                    </th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Created</th>
                                    @if ($currentUser && $currentUser->isAdminHr())
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Action</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($users as $user)
                                    <tr>
                                        <td class="ps-4 text-start">
                                            <h6 class="mb-0 text-sm">{{ $user->name }}</h6>
                                            <p class="text-xs text-secondary mb-0">ID: {{ $user->id }}</p>
                                        </td>
                                        <td class="text-start">
                                            <span class="text-sm font-weight-bold">{{ $user->email }}</span>
                                        </td>
                                        <td class="text-start">
                                            <span class="text-secondary text-sm">{{ $user->tenant?->name ?? '—' }}</span>
                                        </td>
                                        <td class="text-start">
                                            @if ($user->employee)
                                                <span class="text-secondary text-sm">{{ $user->employee->employee_code }} - {{ $user->employee->name }}</span>
                                            @else
                                                <span class="badge bg-gradient-secondary">Not linked</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-gradient-info">{{ $user->roleName() ?? ucfirst(str_replace('_', ' ', (string) $user->roleKey())) }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $user->status === 'active' ? 'bg-gradient-success' : 'bg-gradient-secondary' }}">{{ ucfirst($user->status) }}</span>
                                        </td>
                                        <td class="text-start">
                                            <span class="text-secondary text-sm">{{ optional($user->created_at)->format('d/m/Y') }}</span>
                                        </td>
                                        @if ($currentUser && $currentUser->isAdminHr())
                                            <td class="text-start">
                                                <div class="d-flex align-items-center gap-3">
                                                    <a href="{{ route('users.show-profile', $user) }}" class="mx-1" data-bs-toggle="tooltip" title="Lihat pengguna" data-testid="btn-view-user-{{ $user->id }}">
                                                        <i class="fas fa-eye text-info"></i>
                                                    </a>
                                                    <a href="{{ route('users.profile-edit', $user) }}" class="mx-1" data-bs-toggle="tooltip" title="Edit pengguna" data-testid="btn-edit-user-{{ $user->id }}">
                                                        <i class="fas fa-edit text-secondary"></i>
                                                    </a>
                                                    <button type="button" class="border-0 bg-transparent p-0 mx-1" data-bs-toggle="modal" data-bs-target="#deleteUserModal-{{ $user->id }}" title="Hapus pengguna" data-testid="btn-delete-user-{{ $user->id }}">
                                                        <i class="fas fa-trash text-danger"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 pt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <p class="text-sm text-secondary mb-0">Menampilkan {{ $users->count() }} dari total {{ $users->total() }} pengguna.</p>
                        {{ $users->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if ($currentUser && $currentUser->isAdminHr())
    @foreach ($users as $user)
        <div class="modal fade" id="deleteUserModal-{{ $user->id }}" tabindex="-1" aria-labelledby="deleteUserModalLabel-{{ $user->id }}" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteUserModalLabel-{{ $user->id }}">Konfirmasi Hapus User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        Apakah Anda yakin ingin menghapus user <strong>{{ $user->name }}</strong>?
                    </div>
                    <div class="modal-footer">
                        <form action="{{ route('users.destroy', $user) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="button" class="btn btn-light mb-0" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i> Batal
                            </button>
                            <button type="submit" class="btn btn-danger mb-0" data-testid="confirm-delete-user-{{ $user->id }}">
                                <i class="fas fa-trash me-1"></i> Hapus
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endif

@endsection
