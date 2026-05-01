@extends('layouts.user_type.auth')

@section('suppress-global-flash', '1')

@section('content')

@php($currentUser = auth()->user())
@php($activeUserFilters = array_filter([
    $selectedTenantName ? 'Tenant: '.$selectedTenantName : null,
    $selectedRole && isset($roles[$selectedRole]) ? 'Role: '.$roles[$selectedRole] : null,
    $selectedLinked ? 'Linked: '.ucfirst($selectedLinked) : null,
]))

<div class="row humana-mobile-shell">
    <div class="col-12">
        <x-flash-messages />

        <div class="card mb-4 mx-4 humana-mobile-card">
            <div class="card-header pb-0">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3">
                    <div>
                        <h5 class="mb-0">User Management</h5>
                        <p class="text-sm mb-0">Manage users by tenant, role, and status.</p>
                        <div class="mt-2 d-flex gap-2 flex-wrap humana-summary-scroll">
                            <span class="badge bg-gradient-success">Employee linked: {{ $linkedEmployeeCount }}</span>
                            <span class="badge bg-gradient-secondary">Employee unlinked: {{ $unlinkedEmployeeCount }}</span>
                        </div>
                        @if ($currentUser && $currentUser->isManager())
                        <p class="text-xs text-secondary mb-0 mt-2">
                            Tenant scope active: you are only viewing users from {{ $currentUser->tenant?->name ?? 'your tenant' }}.
                        </p>
                        @endif
                    </div>
                    <div class="d-flex flex-column align-items-lg-end gap-2 w-100 w-lg-auto">
                        @if (count($activeUserFilters) > 0)
                        <div class="d-flex gap-2 flex-wrap justify-content-lg-end align-items-center">
                            <span class="badge bg-gradient-info">Active filters</span>
                            @foreach ($activeUserFilters as $activeFilter)
                                <span class="badge bg-gradient-light text-dark">{{ $activeFilter }}</span>
                            @endforeach
                        </div>
                        @endif
                        <div class="d-flex flex-wrap gap-2 w-100 justify-content-lg-end humana-mobile-actions">
                            <a href="{{ route('users.export', array_merge(request()->query(), ['format' => 'csv'])) }}" class="btn btn-outline-dark btn-sm mb-0">
                                <i class="fas fa-file-csv me-1"></i> CSV
                            </a>
                            <a href="{{ route('users.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}" class="btn btn-outline-success btn-sm mb-0">
                                <i class="fas fa-file-excel me-1"></i> XLSX
                            </a>
                            @if ($currentUser && $currentUser->isAdminHr())
                            <a href="{{ route('users.create') }}" class="btn bg-gradient-primary btn-sm mb-0">
                                <i class="fas fa-plus me-1"></i> New User
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
                <form action="{{ route('users.index') }}" method="GET" class="row mt-3 g-3 humana-mobile-filter">
                    @if ($currentUser && $currentUser->isAdminHr())
                    <div class="col-12 col-md-4 col-sm-6">
                        <label class="form-label">Filter by Tenant</label>
                        <select name="tenant_id" class="form-control">
                            <option value="">All Tenants</option>
                            @foreach ($tenants as $tenant)
                                <option value="{{ $tenant->id }}" @selected($selectedTenantId === $tenant->id)>
                                    {{ $tenant->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-12 col-md-4 col-sm-6">
                        <label class="form-label">Filter by Role</label>
                        <select name="role" class="form-control">
                            <option value="" @selected($selectedRole === null)>All Roles</option>
                            @foreach ($roles as $roleValue => $roleLabel)
                                <option value="{{ $roleValue }}" @selected($selectedRole === $roleValue)>{{ $roleLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-4 col-sm-6">
                        <label class="form-label">Filter by Link Status</label>
                        <div class="d-flex gap-2 flex-column flex-sm-row">
                            <select name="linked" class="form-control">
                                <option value="" @selected($selectedLinked === null)>All Users</option>
                                <option value="only" @selected($selectedLinked === 'only')>Linked only</option>
                                <option value="unlinked" @selected($selectedLinked === 'unlinked')>Unlinked only</option>
                            </select>
                            <button type="submit" class="btn bg-gradient-dark mb-0 flex-sm-shrink-0">
                                <i class="fas fa-filter me-1"></i> Filter
                            </button>
                            <a href="{{ route('users.index') }}" class="btn btn-light mb-0 flex-sm-shrink-0">Reset</a>
                        </div>
                    </div>
                </form>
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <div class="humana-mobile-list px-3 pt-3">
                    <div class="d-grid gap-3 humana-bottom-safe">
                        @forelse ($users as $user)
                            <div class="humana-attendance-item humana-user-card" data-testid="user-mobile-card-{{ $user->id }}">
                                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                    <div class="d-flex align-items-center gap-3 min-width-0">
                                        <div class="avatar avatar-sm bg-gradient-dark border-radius-md d-flex align-items-center justify-content-center flex-shrink-0">
                                            <span class="text-white text-xs font-weight-bold">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                        </div>
                                        <div class="min-width-0">
                                            <h6 class="text-sm mb-0 text-truncate">{{ $user->name }}</h6>
                                            <p class="text-xs text-secondary mb-0 text-truncate">{{ $user->email }}</p>
                                        </div>
                                    </div>
                                    <span class="badge badge-sm {{ $user->status === 'active' ? 'bg-gradient-success' : 'bg-gradient-secondary' }}">{{ ucfirst($user->status) }}</span>
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <p class="text-xxs text-secondary mb-1">Tenant</p>
                                        <p class="text-xs font-weight-bold mb-0">{{ $user->tenant?->name ?? '-' }}</p>
                                    </div>
                                    <div class="col-6">
                                        <p class="text-xxs text-secondary mb-1">Role</p>
                                        <span class="badge badge-sm bg-gradient-info">{{ $user->roleName() ?? ucfirst(str_replace('_', ' ', (string) $user->roleKey())) }}</span>
                                    </div>
                                    <div class="col-12">
                                        <p class="text-xxs text-secondary mb-1">Linked Employee</p>
                                        @if ($user->employee)
                                            <p class="text-xs font-weight-bold mb-0">{{ $user->employee->employee_code }} - {{ $user->employee->name }}</p>
                                        @else
                                            <span class="badge badge-sm bg-gradient-secondary">Not linked</span>
                                        @endif
                                    </div>
                                </div>

                                @if ($currentUser && $currentUser->isAdminHr())
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('users.show-profile', $user) }}" class="btn btn-outline-info btn-sm mb-0 flex-fill">
                                            <i class="fas fa-eye me-1"></i> View
                                        </a>
                                        <a href="{{ route('users.profile-edit', $user) }}" class="btn btn-outline-secondary btn-sm mb-0 flex-fill">
                                            <i class="fas fa-edit me-1"></i> Edit
                                        </a>
                                        <button type="button" class="btn btn-outline-danger btn-sm mb-0 flex-fill" data-bs-toggle="modal" data-bs-target="#deleteUserModal-{{ $user->id }}">
                                            <i class="fas fa-trash me-1"></i> Delete
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="text-center py-5 text-sm text-secondary">No users found.</div>
                        @endforelse
                    </div>
                </div>

                <div class="table-responsive p-0 humana-desktop-table">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">ID</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Name</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Email</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Tenant</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Linked Employee</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Role</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Status</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Created</th>
                                @if ($currentUser && $currentUser->isAdminHr())
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Action</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $user)
                                <tr>
                                    <td class="ps-4">
                                        <p class="text-xs font-weight-bold mb-0">{{ $user->id }}</p>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column justify-content-center">
                                            <h6 class="mb-0 text-sm">{{ $user->name }}</h6>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <p class="text-xs font-weight-bold mb-0">{{ $user->email }}</p>
                                    </td>
                                    <td class="text-center">
                                        <span class="text-secondary text-xs font-weight-bold">{{ $user->tenant?->name ?? '-' }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if ($user->employee)
                                            <span class="text-secondary text-xs font-weight-bold">{{ $user->employee->employee_code }} - {{ $user->employee->name }}</span>
                                        @else
                                            <span class="badge badge-sm bg-gradient-secondary">Not linked</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-sm bg-gradient-info">{{ $user->roleName() ?? ucfirst(str_replace('_', ' ', (string) $user->roleKey())) }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-sm {{ $user->status === 'active' ? 'bg-gradient-success' : 'bg-gradient-secondary' }}">{{ ucfirst($user->status) }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="text-secondary text-xs font-weight-bold">{{ optional($user->created_at)->format('d/m/Y') }}</span>
                                    </td>
                                    @if ($currentUser && $currentUser->isAdminHr())
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center align-items-center gap-3">
                                            <a href="{{ route('users.show-profile', $user) }}" class="mx-1" data-bs-toggle="tooltip" title="View user">
                                                <i class="fas fa-eye text-info"></i>
                                            </a>
                                            <a href="{{ route('users.profile-edit', $user) }}" class="mx-1" data-bs-toggle="tooltip" title="Edit user">
                                                <i class="fas fa-edit text-secondary"></i>
                                            </a>
                                            <button type="button" class="border-0 bg-transparent p-0 mx-1" data-bs-toggle="modal" data-bs-target="#deleteUserModal-{{ $user->id }}" title="Delete user">
                                                <i class="fas fa-trash text-danger"></i>
                                            </button>
                                        </div>
                                    </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $currentUser && $currentUser->isAdminHr() ? 9 : 8 }}" class="text-center py-4 text-sm text-secondary">No users found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-4 pt-3">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

@if ($currentUser && $currentUser->isAdminHr())
    @foreach ($users as $user)
        <div class="modal fade" id="deleteUserModal-{{ $user->id }}" tabindex="-1" aria-labelledby="deleteUserModalLabel-{{ $user->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteUserModalLabel-{{ $user->id }}">Konfirmasi Hapus User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Apakah Anda yakin ingin menghapus user <strong>{{ $user->name }}</strong>?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light mb-0" data-bs-dismiss="modal">Cancel</button>
                        <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn bg-gradient-danger mb-0">Confirm Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endif

@endsection
