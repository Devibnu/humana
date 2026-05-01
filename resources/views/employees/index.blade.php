@extends('layouts.user_type.auth')

@section('content')

@php($currentUser = auth()->user())
@php($linkedLabels = [
    'only' => 'Linked only',
    'unlinked' => 'Unlinked only',
])
@php($activeEmployeeFilters = collect([
    $selectedTenantName ? 'Tenant: '.$selectedTenantName : null,
    $selectedLinked && isset($linkedLabels[$selectedLinked]) ? $linkedLabels[$selectedLinked] : null,
])->filter()->values())
@php($hasActiveEmployeeFilters = $activeEmployeeFilters->isNotEmpty())
@php($totalEmployees = $linkedCount + $unlinkedCount)

<div class="row">
    <div class="col-12">
        @if ($errors->any())
            <div class="alert alert-danger text-white mx-4">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="card mb-4 mx-4 shadow-xs">
            <div class="card-header pb-0">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <h5 class="mb-1">Daftar Karyawan</h5>
                        <p class="text-sm text-secondary mb-0">Kelola data karyawan lengkap dengan tenant, posisi, departemen, dan koneksi akun pengguna.</p>
                        @if ($currentUser && $currentUser->isManager())
                            <p class="text-xs text-secondary mb-0 mt-2">
                                Tenant scope active: you are only viewing employees from {{ $currentUser->tenant?->name ?? 'your tenant' }}.
                            </p>
                        @endif
                    </div>
                    <div class="d-flex gap-2 flex-wrap align-items-center justify-content-end">
                        @if ($hasActiveEmployeeFilters)
                            <span class="badge bg-gradient-info">Filter aktif</span>
                            @foreach ($activeEmployeeFilters as $activeEmployeeFilter)
                                <span class="badge bg-gradient-light text-dark">{{ $activeEmployeeFilter }}</span>
                            @endforeach
                        @endif
                        <a href="{{ route('employees.export', array_merge(request()->query(), ['format' => 'csv'])) }}" class="btn btn-outline-dark btn-sm mb-0">
                            <i class="fas fa-file-csv me-1"></i> Export CSV
                        </a>
                        <a href="{{ route('employees.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}" class="btn btn-outline-success btn-sm mb-0">
                            <i class="fas fa-file-excel me-1"></i> Export Excel
                        </a>
                        @if ($currentUser && $currentUser->isAdminHr())
                            <a href="{{ route('employees.create') }}" class="btn bg-gradient-primary btn-sm mb-0">
                                <i class="fas fa-plus me-1"></i> Tambah Karyawan
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card-body px-0 pt-0 pb-2">
                <div class="px-4 pt-4">
                    <div class="row g-3 mb-4">
                        <div class="col-xl-4 col-md-6">
                            <div class="card border shadow-xs h-100" data-testid="employees-summary-total">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Total Karyawan</p>
                                    <h5 class="mb-0">{{ $totalEmployees }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <div class="card border shadow-xs h-100" data-testid="employees-summary-linked">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Employee Linked</p>
                                    <h5 class="mb-0 text-success">Linked: {{ $linkedCount }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-12">
                            <div class="card border shadow-xs h-100" data-testid="employees-summary-unlinked">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Employee Unlinked</p>
                                    <h5 class="mb-0 text-secondary">Unlinked: {{ $unlinkedCount }}</h5>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border border-radius-xl p-3 mb-4 bg-gray-100">
                        <form action="{{ route('employees.index') }}" method="GET" class="row g-3 align-items-end" data-testid="employees-filter-form">
                            @if ($currentUser && $currentUser->isAdminHr())
                                <div class="col-lg-4 col-md-6">
                                    <label for="tenant_id" class="form-label">Tenant</label>
                                    <select name="tenant_id" id="tenant_id" class="form-control" data-testid="employees-tenant-filter">
                                        <option value="">Semua Tenant</option>
                                        @foreach ($tenants as $tenant)
                                            <option value="{{ $tenant->id }}" @selected($selectedTenantId === $tenant->id)>
                                                {{ $tenant->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="col-lg-4 col-md-6">
                                <label for="linked" class="form-label">Status Koneksi Akun</label>
                                <select name="linked" id="linked" class="form-control" data-testid="employees-linked-filter">
                                    <option value="" @selected($selectedLinked === null)>Semua Karyawan</option>
                                    <option value="only" @selected($selectedLinked === 'only')>Linked only</option>
                                    <option value="unlinked" @selected($selectedLinked === 'unlinked')>Unlinked only</option>
                                </select>
                            </div>
                            <div class="col-lg-4 col-md-12 ms-auto">
                                <div class="d-flex gap-2 justify-content-lg-end mt-lg-4">
                                    <button type="submit" class="btn bg-gradient-dark mb-0" data-testid="btn-apply-employee-filter">
                                        <i class="fas fa-filter me-1"></i> Terapkan
                                    </button>
                                    <a href="{{ route('employees.index') }}" class="btn btn-light mb-0" data-testid="btn-reset-employee-filter">
                                        Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                @if ($employees->isEmpty())
                    <div class="text-center py-5" data-testid="{{ $hasActiveEmployeeFilters ? 'employees-filter-empty-state' : 'employees-empty-state' }}">
                        <i class="fas fa-users fa-3x text-secondary mb-3"></i>
                        @if ($hasActiveEmployeeFilters)
                            <p class="text-secondary mb-1">Tidak ada karyawan yang cocok dengan filter saat ini.</p>
                            <p class="text-sm text-secondary mb-3">Coba ubah tenant atau status koneksi akun untuk melihat hasil lain.</p>
                            <a href="{{ route('employees.index') }}" class="btn btn-light btn-sm mb-0">Reset Filter</a>
                        @else
                            <p class="text-secondary mb-0">Belum ada karyawan, silakan tambah terlebih dahulu</p>
                        @endif
                    </div>
                @else
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0" data-testid="employees-table">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4 text-start">Kode</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Nama Karyawan</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Email</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Tenant</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Linked User</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Posisi</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Departemen</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">No. Telepon</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">
                                        <span class="d-inline-flex align-items-center gap-1">
                                            Status
                                            <i class="fas fa-info-circle text-secondary text-xs" data-bs-toggle="tooltip" title="Status menunjukkan apakah karyawan masih aktif digunakan."></i>
                                        </span>
                                    </th>
                                    @if ($currentUser && $currentUser->isAdminHr())
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Action</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($employees as $employee)
                                    <tr>
                                        <td class="ps-4 text-start">
                                            <span class="text-sm font-weight-bold">{{ $employee->employee_code }}</span>
                                        </td>
                                        <td class="text-start">
                                            <h6 class="mb-0 text-sm">{{ $employee->name }}</h6>
                                        </td>
                                        <td class="text-start">
                                            <span class="text-secondary text-sm">{{ $employee->email }}</span>
                                        </td>
                                        <td class="text-start">
                                            <span class="text-secondary text-sm">{{ $employee->tenant?->name ?? '—' }}</span>
                                        </td>
                                        <td class="text-start">
                                            @if ($employee->user)
                                                <span class="text-secondary text-sm">{{ $employee->user->email }}</span>
                                            @else
                                                <span class="badge bg-gradient-secondary">Not linked</span>
                                            @endif
                                        </td>
                                        <td class="text-start">
                                            <span class="text-secondary text-sm">{{ $employee->position?->name ?? '—' }}</span>
                                        </td>
                                        <td class="text-start">
                                            <span class="text-secondary text-sm">{{ $employee->department?->name ?? '—' }}</span>
                                        </td>
                                        <td class="text-start">
                                            <span class="text-secondary text-sm">{{ $employee->phone ?? '—' }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-sm {{ $employee->status === 'active' ? 'bg-gradient-success' : 'bg-gradient-secondary' }}">
                                                {{ $employee->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </td>
                                        @if ($currentUser && $currentUser->isAdminHr())
                                            <td class="text-start">
                                                <div class="d-flex align-items-center gap-3" data-testid="employee-actions-{{ $employee->id }}">
                                                    <a href="{{ route('employees.show', $employee) }}" class="btn btn-link text-info p-0 mx-1" data-bs-toggle="tooltip" title="Detail" aria-label="Detail" data-testid="employee-detail-{{ $employee->id }}">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('employees.edit', $employee) }}" class="btn btn-link text-warning p-0 mx-1" data-bs-toggle="tooltip" title="Edit" aria-label="Edit" data-testid="employee-edit-{{ $employee->id }}">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-link text-danger p-0 mx-1" data-bs-toggle="modal" data-bs-target="#deleteEmployeeIndexModal-{{ $employee->id }}" title="Delete" aria-label="Delete" data-testid="employee-delete-{{ $employee->id }}">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        @endif
                                    </tr>

                                    @if ($currentUser && $currentUser->isAdminHr())
                                        <div class="modal fade" id="deleteEmployeeIndexModal-{{ $employee->id }}" tabindex="-1" aria-labelledby="deleteEmployeeIndexModalLabel-{{ $employee->id }}" aria-hidden="true" data-testid="employee-index-delete-modal-{{ $employee->id }}">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="deleteEmployeeIndexModalLabel-{{ $employee->id }}">Konfirmasi Hapus Karyawan</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        Apakah Anda yakin ingin menghapus karyawan <strong>{{ $employee->name }}</strong>?
                                                    </div>
                                                    <div class="modal-footer">
                                                        <form action="{{ route('employees.destroy', $employee) }}" method="POST">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="button" class="btn btn-light mb-0" data-bs-dismiss="modal">
                                                                <i class="fas fa-times me-1"></i> Batal
                                                            </button>
                                                            <button type="submit" class="btn btn-danger mb-0" data-testid="confirm-delete-employee-{{ $employee->id }}">
                                                                <i class="fas fa-trash me-1"></i> Hapus
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 pt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <p class="text-sm text-secondary mb-0">Menampilkan {{ $employees->count() }} dari total {{ $employees->total() }} karyawan.</p>
                        {{ $employees->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
