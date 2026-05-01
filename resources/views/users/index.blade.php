@extends('layouts.user_type.auth')

@section('suppress-global-flash', '1')

@section('content')

@php($currentUser = auth()->user())
@php($activeUserFilters = array_filter([
    $selectedTenantName ? 'Tenant: '.$selectedTenantName : null,
    $selectedRole && isset($roles[$selectedRole]) ? 'Role: '.$roles[$selectedRole] : null,
    $selectedLinked ? 'Linked: '.ucfirst($selectedLinked) : null,
]))

<div class="row">
    <div class="col-12">
        <x-flash-messages />

        <div class="card mb-4 mx-4">
            <div class="card-header pb-0">
                <div class="d-flex flex-row justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">User Management</h5>
                        <p class="text-sm mb-0">Manage users by tenant, role, and status.</p>
                        <div class="mt-2 d-flex gap-2 flex-wrap">
                            <span class="badge bg-gradient-success">Employee linked: {{ $linkedEmployeeCount }}</span>
                            <span class="badge bg-gradient-secondary">Employee unlinked: {{ $unlinkedEmployeeCount }}</span>
                        </div>
                        @if ($currentUser && $currentUser->isManager())
                        <p class="text-xs text-secondary mb-0 mt-2">
                            Tenant scope active: you are only viewing users from {{ $currentUser->tenant?->name ?? 'your tenant' }}.
                        </p>
                        @endif
                    </div>
                    <div class="d-flex gap-2">
                        @if (count($activeUserFilters) > 0)
                        <div class="d-flex gap-2 flex-wrap justify-content-end align-items-center">
                            <span class="badge bg-gradient-info">Active filters</span>
                            @foreach ($activeUserFilters as $activeFilter)
                                <span class="badge bg-gradient-light text-dark">{{ $activeFilter }}</span>
                            @endforeach
                        </div>
                        @endif
                        <a href="{{ route('users.export', array_merge(request()->query(), ['format' => 'csv'])) }}" class="btn btn-outline-dark btn-sm mb-0">Export CSV</a>
                        <a href="{{ route('users.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}" class="btn btn-outline-success btn-sm mb-0">Export XLSX</a>
                        @if ($currentUser && $currentUser->isAdminHr())
                        <a href="{{ route('users.create') }}" class="btn bg-gradient-primary btn-sm mb-0">+ New User</a>
                        @endif
                    </div>
                </div>
                <form action="{{ route('users.index') }}" method="GET" class="row mt-3">
                    @if ($currentUser && $currentUser->isAdminHr())
                    <div class="col-md-4 col-sm-6 mb-3 mb-md-0">
                        <label class="form-label">Filter by Tenant</label>
                        <div class="d-flex gap-2">
                            <select name="tenant_id" class="form-control">
                                <option value="">All Tenants</option>
                                @foreach ($tenants as $tenant)
                                    <option value="{{ $tenant->id }}" @selected($selectedTenantId === $tenant->id)>
                                        {{ $tenant->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @endif
                    <div class="col-md-4 col-sm-6 mb-3 mb-md-0">
                        <label class="form-label">Filter by Role</label>
                        <div class="d-flex gap-2">
                            <select name="role" class="form-control">
                                <option value="" @selected($selectedRole === null)>All Roles</option>
                                @foreach ($roles as $roleValue => $roleLabel)
                                    <option value="{{ $roleValue }}" @selected($selectedRole === $roleValue)>{{ $roleLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-6">
                        <label class="form-label">Filter by Link Status</label>
                        <div class="d-flex gap-2">
                            <select name="linked" class="form-control">
                                <option value="" @selected($selectedLinked === null)>All Users</option>
                                <option value="only" @selected($selectedLinked === 'only')>Linked only</option>
                                <option value="unlinked" @selected($selectedLinked === 'unlinked')>Unlinked only</option>
                            </select>
                            <button type="submit" class="btn bg-gradient-dark mb-0">Filter</button>
                            <a href="{{ route('users.index') }}" class="btn btn-light mb-0">Reset</a>
                        </div>
                    </div>
                </form>
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <div class="table-responsive p-0">
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

                                        <div class="modal fade" id="deleteUserModal-{{ $user->id }}" tabindex="-1" aria-labelledby="deleteUserModalLabel-{{ $user->id }}" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="deleteUserModalLabel-{{ $user->id }}">Konfirmasi Hapus User</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        Apakah Anda yakin ingin menghapus user ini?
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

@endsection