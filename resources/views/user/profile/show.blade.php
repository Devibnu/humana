@extends('layouts.user_type.auth')

@section('content')

<div class="row">
    <div class="col-12">

        <x-flash-messages />

        <div class="card mx-4 mb-4 shadow-xs">
            <div class="card-header pb-0">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        @if ($avatarUrl)
                            <img src="{{ $avatarUrl }}" alt="{{ $user->name }} avatar"
                                class="avatar avatar-xxl border-radius-xl shadow-sm object-fit-cover">
                        @else
                            <div class="avatar avatar-xxl border-radius-xl bg-gradient-dark d-flex align-items-center justify-content-center shadow-sm">
                                <span class="text-white text-xl font-weight-bold">{{ $avatarInitials }}</span>
                            </div>
                        @endif

                        <div>
                            <h5 class="mb-1">{{ $user->name }}</h5>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="badge {{ $roleBadgeClass }}">{{ $user->roleName() ?? ucfirst(str_replace('_', ' ', (string) $user->roleKey())) }}</span>
                                <span class="badge bg-gradient-{{ $user->status === 'active' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($user->status) }}
                                </span>
                            </div>
                            <p class="text-sm text-secondary mb-0 mt-2">Review account data and linked employee information before taking admin actions.</p>
                        </div>
                    </div>

                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('users.profile-edit', $user) }}" class="btn btn-outline-secondary btn-sm mb-0">
                            <i class="fas fa-edit me-1"></i> Edit
                        </a>

                        <button type="button" class="btn btn-outline-danger btn-sm mb-0"
                            data-bs-toggle="modal" data-bs-target="#deleteUserDetailModal-{{ $user->id }}">
                            <i class="fas fa-trash me-1"></i> Delete
                        </button>

                        <a href="{{ route('users.index') }}" class="btn btn-light btn-sm mb-0">
                            <i class="fas fa-arrow-left me-1"></i> Back
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="card border shadow-xs h-100">
                            <div class="card-header pb-0">
                                <h6 class="mb-0">Account Information</h6>
                                <p class="text-xs text-secondary mb-0">Identity, role, tenant, and account status.</p>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Name</label>
                                    <input type="text" class="form-control" value="{{ $user->name }}" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="text" class="form-control" value="{{ $user->email }}" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Role</label>
                                    <input type="text" class="form-control" value="{{ $user->roleName() ?? ucfirst(str_replace('_', ' ', (string) $user->roleKey())) }}" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tenant</label>
                                    <input type="text" class="form-control" value="{{ $user->tenant?->name ?? '-' }}" readonly>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label">Status</label>
                                    <input type="text" class="form-control" value="{{ ucfirst($user->status) }}" readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card border shadow-xs h-100">
                            <div class="card-header pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div>
                                    <h6 class="mb-0">Linked Employee</h6>
                                    <p class="text-xs text-secondary mb-0">Employee record attached to this account.</p>
                                </div>
                                @if (! $employee)
                                    <a href="{{ route('users.link-employee', $user) }}" class="btn bg-gradient-primary btn-sm mb-0">
                                        Link Employee
                                    </a>
                                @endif
                            </div>
                            <div class="card-body">
                                @if ($employee)
                                    <div class="mb-3">
                                        <label class="form-label">NIK</label>
                                        <input type="text" class="form-control" value="{{ $employee->employee_code }}" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Department</label>
                                        <input type="text" class="form-control" value="{{ $employee->department?->name ?? '-' }}" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Position</label>
                                        <input type="text" class="form-control" value="{{ $employee->position?->name ?? '-' }}" readonly>
                                    </div>
                                    <div class="mb-0">
                                        <label class="form-label">Work Location</label>
                                        <input type="text" class="form-control" value="{{ $employee->workLocation?->name ?? '-' }}" readonly>
                                    </div>
                                @else
                                    <div class="border border-secondary border-radius-md p-3 bg-gray-100">
                                        <p class="text-sm font-weight-bold mb-1">Employee record not linked</p>
                                        <p class="text-sm text-secondary mb-0">Link this account to an employee record so HR data becomes connected and easier to manage.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="deleteUserDetailModal-{{ $user->id }}" tabindex="-1"
            aria-labelledby="deleteUserDetailModalLabel-{{ $user->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteUserDetailModalLabel-{{ $user->id }}">Konfirmasi Hapus User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Apakah Anda yakin ingin menghapus user <strong>{{ $user->name }}</strong>? Tindakan ini tidak dapat dibatalkan.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light mb-0" data-bs-dismiss="modal">Cancel</button>
                        <form action="{{ route('users.show-profile.destroy', $user) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn bg-gradient-danger mb-0">Confirm Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

@endsection