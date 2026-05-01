@extends('layouts.user_type.auth')

@section('content')

<div class="row">
    <div class="col-12">
        <x-flash-messages />

        <div class="card mb-4 mx-4 shadow-xs">
            <div class="card-header pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-1"><i class="fas fa-building me-2 text-primary"></i>Panel Owner Tenant</h5>
                    <p class="text-sm text-secondary mb-0">Kelola tenant secara global, termasuk paket subscription dan ringkasan data utama tiap tenant.</p>
                </div>
                <button type="button" class="btn bg-gradient-primary btn-sm mb-0" data-bs-toggle="modal" data-bs-target="#ownerAddTenantModal" data-testid="owner-add-tenant-button">
                    <i class="fas fa-plus me-1"></i> Tambah Tenant
                </button>
            </div>

            <div class="card-body px-0 pt-0 pb-2">
                @if ($tenants->isEmpty())
                    <div class="text-center py-5" data-testid="owner-tenant-empty-state">
                        <i class="fas fa-building fa-3x text-secondary mb-3"></i>
                        <p class="text-secondary mb-0">Belum ada tenant untuk dikelola.</p>
                    </div>
                @else
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0" data-testid="owner-tenants-table">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Nama Tenant</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Domain</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Status</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Subscription</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Jumlah User</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Jumlah Karyawan</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Jumlah Departemen</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($tenants as $tenant)
                                    @php($subscriptionBadge = ['basic' => 'bg-gradient-secondary', 'pro' => 'bg-gradient-info', 'enterprise' => 'bg-gradient-dark'][$tenant->subscription_plan] ?? 'bg-gradient-secondary')
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex flex-column">
                                                <h6 class="mb-0 text-sm">{{ $tenant->name }}</h6>
                                                <span class="text-xs text-secondary">{{ $tenant->description ?: 'Tidak ada deskripsi tenant' }}</span>
                                            </div>
                                        </td>
                                        <td><span class="text-sm text-secondary">{{ $tenant->domain }}</span></td>
                                        <td class="text-center">
                                            <span class="badge badge-sm {{ $tenant->status === 'active' ? 'bg-gradient-success' : 'bg-gradient-danger' }}" data-testid="owner-tenant-status-{{ $tenant->id }}">{{ $statuses[$tenant->status] ?? ucfirst($tenant->status) }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-sm {{ $subscriptionBadge }}" data-testid="owner-tenant-plan-{{ $tenant->id }}">{{ $subscriptionPlans[$tenant->subscription_plan] ?? ucfirst($tenant->subscription_plan) }}</span>
                                        </td>
                                        <td class="text-center"><span class="text-secondary text-xs font-weight-bold" data-testid="owner-tenant-users-count-{{ $tenant->id }}">{{ $tenant->users_count }}</span></td>
                                        <td class="text-center"><span class="text-secondary text-xs font-weight-bold" data-testid="owner-tenant-employees-count-{{ $tenant->id }}">{{ $tenant->employees_count }}</span></td>
                                        <td class="text-center"><span class="text-secondary text-xs font-weight-bold" data-testid="owner-tenant-departments-count-{{ $tenant->id }}">{{ $tenant->departments_count }}</span></td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center align-items-center gap-3">
                                                <button type="button" class="border-0 bg-transparent p-0" data-bs-toggle="modal" data-bs-target="#ownerTenantDetailModal-{{ $tenant->id }}" title="Detail tenant" data-testid="owner-tenant-detail-{{ $tenant->id }}">
                                                    <i class="fas fa-eye text-info"></i>
                                                </button>
                                                <button type="button" class="border-0 bg-transparent p-0" data-bs-toggle="modal" data-bs-target="#ownerTenantEditModal-{{ $tenant->id }}" title="Edit tenant" data-testid="owner-tenant-edit-{{ $tenant->id }}">
                                                    <i class="fas fa-edit text-warning"></i>
                                                </button>
                                                <form action="{{ route('owner.tenants.destroy', $tenant) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="border-0 bg-transparent p-0" title="Hapus tenant" data-testid="owner-tenant-delete-{{ $tenant->id }}" onclick="return confirm('Hapus tenant ini?')">
                                                        <i class="fas fa-trash text-danger"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>

                                    <div class="modal fade" id="ownerTenantDetailModal-{{ $tenant->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Detail Tenant</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <label class="form-label">Nama Tenant</label>
                                                            <input type="text" class="form-control" value="{{ $tenant->name }}" readonly>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">Domain</label>
                                                            <input type="text" class="form-control" value="{{ $tenant->domain }}" readonly>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label">Status</label>
                                                            <input type="text" class="form-control" value="{{ $statuses[$tenant->status] ?? ucfirst($tenant->status) }}" readonly>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label">Subscription</label>
                                                            <input type="text" class="form-control" value="{{ $subscriptionPlans[$tenant->subscription_plan] ?? ucfirst($tenant->subscription_plan) }}" readonly>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label">Kode</label>
                                                            <input type="text" class="form-control" value="{{ $tenant->code ?? '-' }}" readonly>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label">Jumlah User</label>
                                                            <input type="text" class="form-control" value="{{ $tenant->users_count }}" readonly>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label">Jumlah Karyawan</label>
                                                            <input type="text" class="form-control" value="{{ $tenant->employees_count }}" readonly>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label">Jumlah Departemen</label>
                                                            <input type="text" class="form-control" value="{{ $tenant->departments_count }}" readonly>
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="form-label">Deskripsi</label>
                                                            <textarea class="form-control" rows="3" readonly>{{ $tenant->description ?: 'Tidak ada deskripsi tenant' }}</textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal fade" id="ownerTenantEditModal-{{ $tenant->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Tenant</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                                </div>
                                                <form action="{{ route('owner.tenants.update', $tenant) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-body">
                                                        <div class="row g-3">
                                                            <div class="col-md-6">
                                                                <label class="form-label">Nama Tenant <span class="text-danger">*</span></label>
                                                                <input type="text" name="name" class="form-control" value="{{ $tenant->name }}" required>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">Domain <span class="text-danger">*</span></label>
                                                                <input type="text" name="domain" class="form-control" value="{{ $tenant->domain }}" required>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                                                <select name="status" class="form-control" required>
                                                                    @foreach ($statuses as $value => $label)
                                                                        <option value="{{ $value }}" @selected($tenant->status === $value)>{{ $label }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">Paket Subscription <span class="text-danger">*</span></label>
                                                                <select name="subscription_plan" class="form-control" required>
                                                                    @foreach ($subscriptionPlans as $value => $label)
                                                                        <option value="{{ $value }}" @selected($tenant->subscription_plan === $value)>{{ $label }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="col-12">
                                                                <label class="form-label">Deskripsi</label>
                                                                <textarea name="description" rows="3" class="form-control" placeholder="Deskripsi tenant">{{ $tenant->description }}</textarea>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn bg-gradient-primary">Simpan Perubahan</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="ownerAddTenantModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Tenant Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form action="{{ route('owner.tenants.store') }}" method="POST" data-testid="owner-tenant-create-form">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Tenant <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Domain <span class="text-danger">*</span></label>
                            <input type="text" name="domain" class="form-control @error('domain') is-invalid @enderror" value="{{ old('domain') }}" placeholder="tenant-baru.test" required>
                            @error('domain')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-control @error('status') is-invalid @enderror" required>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', 'active') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Paket Subscription <span class="text-danger">*</span></label>
                            <select name="subscription_plan" class="form-control @error('subscription_plan') is-invalid @enderror" required>
                                @foreach ($subscriptionPlans as $value => $label)
                                    <option value="{{ $value }}" @selected(old('subscription_plan', 'basic') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('subscription_plan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror" placeholder="Deskripsi tenant">{{ old('description') }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn bg-gradient-primary">Simpan Tenant</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($errors->has('name') || $errors->has('domain') || $errors->has('status') || $errors->has('subscription_plan') || $errors->has('description'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modalElement = document.getElementById('ownerAddTenantModal');
            if (modalElement && typeof bootstrap !== 'undefined') {
                bootstrap.Modal.getOrCreateInstance(modalElement).show();
            }
        });
    </script>
@endif

@endsection