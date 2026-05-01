@extends('layouts.user_type.auth')

@section('content')
@php($activeLeaveTypeFilters = collect([
    $search !== '' ? 'Pencarian: '.$search : null,
    $selectedTenantName ? 'Tenant: '.$selectedTenantName : null,
    $selectedFlow && $approvalFlows->has($selectedFlow) ? 'Alur: '.$approvalFlows[$selectedFlow] : null,
])->filter()->values())
@php($hasActiveLeaveTypeFilters = $activeLeaveTypeFilters->isNotEmpty())

<div class="row">
    <div class="col-12">
        <x-flash-messages />

        <div class="card mx-4 mb-4 shadow-xs">
            <div class="card-header pb-0">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <h5 class="mb-1">Jenis Cuti</h5>
                        <p class="text-sm text-secondary mb-0">Kelola aturan master jenis cuti lengkap dengan kebutuhan lampiran, persetujuan atasan, dan alur approval.</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap align-items-center">
                        @if ($hasActiveLeaveTypeFilters)
                            <span class="badge bg-gradient-info">Filter aktif</span>
                            @foreach ($activeLeaveTypeFilters as $activeLeaveTypeFilter)
                                <span class="badge bg-gradient-light text-dark">{{ $activeLeaveTypeFilter }}</span>
                            @endforeach
                        @endif
                        <button type="button" class="btn bg-gradient-primary btn-sm mb-0" data-bs-toggle="modal" data-bs-target="#addLeaveTypeIndexModal" data-testid="btn-open-add-leave-type-modal">
                            <i class="fas fa-plus me-1"></i> Tambah Jenis Cuti
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <div class="px-4 pt-4">
                    <div class="row g-3 mb-4">
                        <div class="col-xl-3 col-md-6">
                            <div class="card border shadow-xs h-100" data-testid="leave-types-summary-total">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Total Jenis Cuti</p>
                                    <h5 class="mb-0">{{ $summary['total'] }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card border shadow-xs h-100" data-testid="leave-types-summary-attachment">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Wajib Lampiran</p>
                                    <h5 class="mb-0 text-warning">{{ $summary['attachments_required'] }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card border shadow-xs h-100" data-testid="leave-types-summary-approval">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Perlu Persetujuan</p>
                                    <h5 class="mb-0 text-success">{{ $summary['approval_required'] }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card border shadow-xs h-100" data-testid="leave-types-summary-auto">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Auto Approval</p>
                                    <h5 class="mb-0 text-info">{{ $summary['auto_approved'] }}</h5>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border border-radius-xl p-3 mb-4 bg-gray-100">
                        <form action="{{ route('jenis-cuti.index') }}" method="GET" class="row g-3 align-items-end" data-testid="leave-types-filter-form">
                            <div class="col-lg-5 col-md-6">
                                <label for="search" class="form-label">Cari Jenis Cuti</label>
                                <div class="input-group">
                                    <span class="input-group-text text-body"><i class="fas fa-search" aria-hidden="true"></i></span>
                                    <input
                                        type="text"
                                        id="search"
                                        name="search"
                                        class="form-control"
                                        value="{{ $search }}"
                                        placeholder="Cari nama jenis cuti atau tenant"
                                        data-testid="leave-types-search-input"
                                    >
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <label for="tenant_id" class="form-label">Tenant</label>
                                <select name="tenant_id" id="tenant_id" class="form-control" data-testid="leave-types-tenant-filter">
                                    <option value="">Semua Tenant</option>
                                    @foreach ($tenants as $tenant)
                                        <option value="{{ $tenant->id }}" @selected($selectedTenantId === $tenant->id)>{{ $tenant->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label for="alur_persetujuan" class="form-label">Alur</label>
                                <select name="alur_persetujuan" id="alur_persetujuan" class="form-control" data-testid="leave-types-flow-filter">
                                    <option value="">Semua Alur</option>
                                    @foreach ($approvalFlows as $flowValue => $flowLabel)
                                        <option value="{{ $flowValue }}" @selected($selectedFlow === $flowValue)>{{ $flowLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <div class="d-flex gap-2 justify-content-lg-end mt-lg-4">
                                    <button type="submit" class="btn bg-gradient-dark mb-0" data-testid="btn-apply-leave-type-filter">
                                        <i class="fas fa-filter me-1"></i> Terapkan
                                    </button>
                                    <a href="{{ route('jenis-cuti.index') }}" class="btn btn-light mb-0" data-testid="btn-reset-leave-type-filter">
                                        Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                @if ($types->isEmpty())
                    <div class="text-center py-5" data-testid="{{ $hasActiveLeaveTypeFilters ? 'leave-types-filter-empty-state' : 'leave-types-empty-state' }}">
                        <i class="fas fa-layer-group fa-3x text-secondary mb-3"></i>
                        @if ($hasActiveLeaveTypeFilters)
                            <p class="text-secondary mb-1">Tidak ada jenis cuti yang cocok dengan pencarian atau filter saat ini.</p>
                            <p class="text-sm text-secondary mb-3">Coba ubah kata kunci, tenant, atau alur approval untuk melihat hasil lain.</p>
                            <a href="{{ route('jenis-cuti.index') }}" class="btn btn-light btn-sm mb-0">Reset Filter</a>
                        @else
                            <p class="text-secondary mb-0">Belum ada data jenis cuti.</p>
                        @endif
                    </div>
                @else
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0" data-testid="leave-types-table">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4 text-start">Jenis Cuti</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Tenant</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Wajib Lampiran</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Wajib Persetujuan</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Alur</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Tipe</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($types as $type)
                                    <tr>
                                        <td class="ps-4 text-start">
                                            <div>
                                                <h6 class="mb-0 text-sm">{{ $type->name }}</h6>
                                                <p class="text-xs text-secondary mb-0 mt-1">Workflow {{ $approvalFlows[$type->alur_persetujuan] ?? ucfirst($type->alur_persetujuan) }}</p>
                                            </div>
                                        </td>
                                        <td class="text-start"><span class="text-secondary text-sm">{{ $type->tenant?->name ?? '—' }}</span></td>
                                        <td class="text-center">
                                            <span class="badge {{ $type->wajib_lampiran ? 'bg-gradient-warning text-dark' : 'bg-gradient-secondary' }}">{{ $type->wajib_lampiran ? 'Ya' : 'Tidak' }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $type->wajib_persetujuan ? 'bg-gradient-success' : 'bg-gradient-secondary' }}">{{ $type->wajib_persetujuan ? 'Ya' : 'Tidak' }}</span>
                                        </td>
                                        <td class="text-center"><span class="badge bg-gradient-info">{{ $approvalFlows[$type->alur_persetujuan] ?? ucfirst($type->alur_persetujuan) }}</span></td>
                                        <td class="text-center">
                                            <span class="badge {{ $type->is_paid ? 'bg-gradient-primary' : 'bg-gradient-dark' }}">{{ $type->is_paid ? 'Cuti Berbayar' : 'Unpaid Leave' }}</span>
                                        </td>
                                        <td class="text-start">
                                            <div class="d-flex align-items-center gap-3 justify-content-start">
                                                <a href="{{ route('jenis-cuti.edit', $type) }}" class="mx-1" data-bs-toggle="tooltip" title="Edit" data-testid="btn-edit-leave-type-{{ $type->id }}"><i class="fas fa-edit text-secondary"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 pt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <p class="text-sm text-secondary mb-0">Menampilkan {{ $types->count() }} dari total {{ $types->total() }} jenis cuti.</p>
                        {{ $types->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addLeaveTypeIndexModal" tabindex="-1" aria-labelledby="addLeaveTypeIndexModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="addLeaveTypeIndexModalLabel">Tambah Jenis Cuti</h5>
                    <p class="text-sm text-secondary mb-0">Lengkapi tenant, nama kebijakan cuti, dan alur approval untuk langsung menambah dari halaman daftar.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                @if ($tenants->isEmpty())
                    <div class="border border-secondary border-radius-md p-4 text-center bg-gray-100" data-testid="leave-types-modal-empty-state">
                        <i class="fas fa-layer-group text-secondary fa-2x mb-3"></i>
                        <h6 class="mb-2">Belum ada tenant, silakan buat terlebih dahulu</h6>
                        <p class="text-sm text-secondary mb-0">Jenis cuti membutuhkan tenant agar kebijakan cuti tetap terhubung ke organisasi yang tepat.</p>
                    </div>
                @else
                    <form action="{{ route('jenis-cuti.store') }}" method="POST" data-testid="leave-types-index-create-form" autocomplete="off">
                        @csrf
                        <input type="text" class="d-none" tabindex="-1" autocomplete="username">
                        <input type="password" class="d-none" tabindex="-1" autocomplete="new-password">
                        @include('jenis_cuti._form', ['type' => $type, 'forceBlankDefaults' => true, 'disableAutofill' => true])
                        <div class="d-flex justify-content-end gap-2 mt-4 flex-wrap">
                            <button type="button" class="btn btn-light mb-0" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i> Batal</button>
                            <a href="{{ route('jenis-cuti.create') }}" class="btn btn-outline-dark mb-0"><i class="fas fa-up-right-from-square me-1"></i> Halaman Penuh</a>
                            <button type="submit" class="btn bg-gradient-primary mb-0"><i class="fas fa-save me-1"></i> Simpan Jenis Cuti</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

@php($hasCreateLeaveTypeErrors = $errors->has('tenant_id') || $errors->has('name') || $errors->has('alur_persetujuan') || $errors->has('wajib_lampiran') || $errors->has('wajib_persetujuan'))

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modalElement = document.getElementById('addLeaveTypeIndexModal');
            var formElement = modalElement ? modalElement.querySelector('[data-testid="leave-types-index-create-form"]') : null;
            var hasCreateLeaveTypeErrors = @json($hasCreateLeaveTypeErrors);

            if (!modalElement || !formElement) {
                return;
            }

            var resetCreateForm = function () {
                if (hasCreateLeaveTypeErrors) {
                    return;
                }

                formElement.reset();

                Array.from(formElement.querySelectorAll('input[type="text"]')).forEach(function (field) {
                    field.value = '';
                });

                var tenantSelect = formElement.querySelector('[data-testid="leave-type-tenant-select"]');
                var flowSelect = formElement.querySelector('[data-testid="leave-type-flow-select"]');
                var attachmentCheckbox = formElement.querySelector('#wajib_lampiran');
                var approvalCheckbox = formElement.querySelector('#wajib_persetujuan');

                if (tenantSelect) {
                    tenantSelect.value = '';
                }

                if (flowSelect) {
                    flowSelect.value = 'single';
                }

                if (attachmentCheckbox) {
                    attachmentCheckbox.checked = false;
                }

                if (approvalCheckbox) {
                    approvalCheckbox.checked = true;
                }
            };

            modalElement.addEventListener('show.bs.modal', resetCreateForm);
            modalElement.addEventListener('shown.bs.modal', resetCreateForm);
        });
    </script>
@endpush

@if ($hasCreateLeaveTypeErrors)
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var modalElement = document.getElementById('addLeaveTypeIndexModal');

                if (modalElement && typeof bootstrap !== 'undefined') {
                    bootstrap.Modal.getOrCreateInstance(modalElement).show();
                }
            });
        </script>
    @endpush
@endif
@endsection
