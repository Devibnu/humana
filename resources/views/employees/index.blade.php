@extends('layouts.user_type.auth')

@section('content')

@php($currentUser = auth()->user())
@php($activeEmployeeFilters = array_filter([
    $selectedTenantName ? 'Tenant: '.$selectedTenantName : null,
    $selectedLinked ? 'Linked: '.ucfirst($selectedLinked) : null,
]))

<div class="row">
    <div class="col-12">
        @if ($errors->any())
            <div class="alert alert-danger text-white">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="card mb-4 mx-4">
            <div class="card-header pb-0">
                <div class="d-flex flex-row justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Employees</h5>
                        <p class="text-sm mb-0">Manage employee records by tenant.</p>
                        <div class="mt-2 d-flex gap-2 flex-wrap">
                            <span class="badge bg-gradient-success">Linked: {{ $linkedCount }}</span>
                            <span class="badge bg-gradient-secondary">Unlinked: {{ $unlinkedCount }}</span>
                        </div>
                        @if ($currentUser && $currentUser->isManager())
                        <p class="text-xs text-secondary mb-0 mt-2">
                            Tenant scope active: you are only viewing employees from {{ $currentUser->tenant?->name ?? 'your tenant' }}.
                        </p>
                        @endif
                    </div>
                    <div class="d-flex gap-2">
                        @if (count($activeEmployeeFilters) > 0)
                        <div class="d-flex gap-2 flex-wrap justify-content-end align-items-center">
                            <span class="badge bg-gradient-info">Active filters</span>
                            @foreach ($activeEmployeeFilters as $activeFilter)
                                <span class="badge bg-gradient-light text-dark">{{ $activeFilter }}</span>
                            @endforeach
                        </div>
                        @endif
                        <a href="{{ route('employees.export', array_merge(request()->query(), ['format' => 'csv'])) }}" class="btn btn-outline-dark btn-sm mb-0">Export CSV</a>
                        <a href="{{ route('employees.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}" class="btn btn-outline-success btn-sm mb-0">Export XLSX</a>
                        @if ($currentUser && $currentUser->isAdminHr())
                        <a href="{{ route('employees.create') }}" class="btn bg-gradient-primary btn-sm mb-0">+ New Employee</a>
                        @endif
                    </div>
                </div>
                <form action="{{ route('employees.index') }}" method="GET" class="row mt-3">
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
                    <div class="col-md-4 col-sm-6">
                        <label class="form-label">Filter by Link Status</label>
                        <div class="d-flex gap-2">
                            <select name="linked" class="form-control">
                                <option value="" @selected($selectedLinked === null)>All Employees</option>
                                <option value="only" @selected($selectedLinked === 'only')>Linked only</option>
                                <option value="unlinked" @selected($selectedLinked === 'unlinked')>Unlinked only</option>
                            </select>
                            <button type="submit" class="btn bg-gradient-dark mb-0">Filter</button>
                            <a href="{{ route('employees.index') }}" class="btn btn-light mb-0">Reset</a>
                        </div>
                    </div>
                </form>
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <div class="table-responsive p-0">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Code</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Name</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Email</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Tenant</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Linked User</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Position</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Department</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Phone</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Status</th>
                                @if ($currentUser && $currentUser->isAdminHr())
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Action</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($employees as $employee)
                                <tr>
                                    <td class="ps-4"><p class="text-xs font-weight-bold mb-0">{{ $employee->employee_code }}</p></td>
                                    <td><h6 class="mb-0 text-sm">{{ $employee->name }}</h6></td>
                                    <td class="text-center"><span class="text-secondary text-xs font-weight-bold">{{ $employee->email }}</span></td>
                                    <td class="text-center"><span class="text-secondary text-xs font-weight-bold">{{ $employee->tenant?->name ?? '-' }}</span></td>
                                    <td class="text-center">
                                        @if ($employee->user)
                                            <span class="text-secondary text-xs font-weight-bold">{{ $employee->user->email }}</span>
                                        @else
                                            <span class="badge badge-sm bg-gradient-secondary">Not linked</span>
                                        @endif
                                    </td>
                                    <td class="text-center"><span class="text-secondary text-xs font-weight-bold">{{ $employee->position?->name ?? '-' }}</span></td>
                                    <td class="text-center"><span class="text-secondary text-xs font-weight-bold">{{ $employee->department?->name ?? '-' }}</span></td>
                                    <td class="text-center"><span class="text-secondary text-xs font-weight-bold">{{ $employee->phone ?? '-' }}</span></td>
                                    <td class="text-center"><span class="badge badge-sm {{ $employee->status === 'active' ? 'bg-gradient-success' : 'bg-gradient-secondary' }}">{{ ucfirst($employee->status) }}</span></td>
                                    @if ($currentUser && $currentUser->isAdminHr())
                                    <td class="text-center">
                                        <div class="d-inline-flex align-items-center gap-2 flex-wrap justify-content-center" data-testid="employee-actions-{{ $employee->id }}">
                                            <a href="{{ route('employees.show', $employee) }}" class="btn btn-link text-info p-0" title="Detail" aria-label="Detail" data-testid="employee-detail-{{ $employee->id }}">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('employees.edit', $employee) }}" class="btn btn-link text-warning p-0" title="Edit" aria-label="Edit" data-testid="employee-edit-{{ $employee->id }}">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        <form action="{{ route('employees.destroy', $employee) }}" method="POST" class="d-inline mb-0">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-link text-danger p-0" title="Delete" aria-label="Delete" data-testid="employee-delete-{{ $employee->id }}" onclick="return confirm('Delete this employee?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        </div>
                                    </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $currentUser && $currentUser->isAdminHr() ? 10 : 9 }}" class="text-center py-4 text-sm text-secondary">No employees found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-4 pt-3">{{ $employees->links() }}</div>
            </div>
        </div>
    </div>
</div>

@endsection