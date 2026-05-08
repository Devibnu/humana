@extends('layouts.user_type.auth')

@section('content')
<div class="row">
    <div class="col-12">
        <x-flash-messages />

        <div class="card mx-4 mb-4 shadow-xs">
            <div class="card-header pb-0 d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h5 class="mb-1">Payroll Policy</h5>
                    <p class="text-sm text-secondary mb-0">Atur kebijakan payroll berdasarkan status karyawan, grade, jabatan, paid leave, unpaid leave, tunjangan, dan insentif.</p>
                </div>
                <a href="{{ route('payroll.policies.create', array_filter(['tenant_id' => $selectedTenantId])) }}" class="btn bg-gradient-primary btn-sm mb-0">
                    <i class="fas fa-plus me-1"></i> Tambah Policy
                </a>
            </div>

            <div class="card-body">
                <form method="GET" action="{{ route('payroll.policies.index') }}" class="row g-3 align-items-end mb-4">
                    <div class="col-md-5">
                        <label class="form-label">Tenant</label>
                        <select name="tenant_id" class="form-control" onchange="this.form.submit()">
                            <option value="">Semua tenant</option>
                            @foreach($tenants as $tenant)
                                <option value="{{ $tenant->id }}" @selected((string) $selectedTenantId === (string) $tenant->id)>{{ $tenant->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-light mb-0"><i class="fas fa-filter me-1"></i> Filter</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="text-xs text-uppercase ps-3">Policy</th>
                                <th class="text-xs text-uppercase">Scope</th>
                                <th class="text-xs text-uppercase">Komponen</th>
                                <th class="text-xs text-uppercase">Prioritas</th>
                                <th class="text-xs text-uppercase text-end pe-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($policies as $policy)
                                <tr>
                                    <td class="ps-3">
                                        <p class="text-sm font-weight-bold mb-1">{{ $policy->name }}</p>
                                        <p class="text-xs text-secondary mb-0">{{ $policy->tenant?->name ?? '-' }}</p>
                                    </td>
                                    <td class="text-sm">
                                        <div class="d-flex flex-wrap gap-1">
                                            <span class="badge bg-gray-100 text-dark">{{ $policy->employment_type ? ucfirst($policy->employment_type) : 'Semua status' }}</span>
                                            <span class="badge bg-gray-100 text-dark">{{ $policy->employee_level_code ?: 'Semua grade' }}</span>
                                            <span class="badge bg-gray-100 text-dark">{{ $policy->position?->name ?? 'Semua jabatan' }}</span>
                                        </div>
                                    </td>
                                    <td class="text-sm">{{ $policy->components->count() }} komponen</td>
                                    <td class="text-sm">{{ $policy->priority }}</td>
                                    <td class="text-end pe-3">
                                        <a href="{{ route('payroll.policies.edit', $policy) }}" class="btn btn-outline-primary btn-xs mb-0">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" action="{{ route('payroll.policies.destroy', $policy) }}" class="d-inline" onsubmit="return confirm('Hapus payroll policy ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-xs mb-0">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-secondary">Belum ada payroll policy.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $policies->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
