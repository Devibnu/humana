@extends('layouts.user_type.auth')

@section('content')
@php($activeDeductionFilters = collect([
    $search !== '' ? 'Pencarian: '.$search : null,
    $selectedSalaryType && $salaryTypes->has($selectedSalaryType) ? 'Tipe Karyawan: '.$salaryTypes[$selectedSalaryType] : null,
    $selectedRateType && $rateTypes->has($selectedRateType) ? 'Rate: '.$rateTypes[$selectedRateType] : null,
])->filter()->values())
@php($hasActiveDeductionFilters = $activeDeductionFilters->isNotEmpty())

<div class="row">
    <div class="col-12">
        <x-flash-messages />

        <div class="card mb-4 mx-4 shadow-xs">
            <div class="card-header pb-0">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <h5 class="mb-1">Aturan Potongan</h5>
                        <p class="text-sm text-secondary mb-0">Kelola aturan potongan absensi berdasarkan tipe karyawan, jam kerja, toleransi telat, dan skema rate payroll.</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap align-items-center">
                        @if ($hasActiveDeductionFilters)
                            <span class="badge bg-gradient-info">Filter aktif</span>
                            @foreach ($activeDeductionFilters as $activeDeductionFilter)
                                <span class="badge bg-gradient-light text-dark">{{ $activeDeductionFilter }}</span>
                            @endforeach
                        @endif
                        <button type="button" class="btn bg-gradient-primary btn-sm mb-0" data-bs-toggle="modal" data-bs-target="#addDeductionRuleIndexModal" data-testid="btn-open-add-deduction-rule-modal">
                            <i class="fas fa-plus me-1"></i> Tambah Potongan
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <div class="px-4 pt-4">
                    <div class="row g-3 mb-4">
                        <div class="col-xl-3 col-md-6">
                            <div class="card border shadow-xs h-100" data-testid="deduction-rules-summary-total">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Total Aturan</p>
                                    <h5 class="mb-0">{{ $summary['total'] }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card border shadow-xs h-100" data-testid="deduction-rules-summary-daily">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Karyawan Harian</p>
                                    <h5 class="mb-0 text-warning">{{ $summary['daily'] }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card border shadow-xs h-100" data-testid="deduction-rules-summary-monthly">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Karyawan Bulanan</p>
                                    <h5 class="mb-0 text-dark">{{ $summary['monthly'] }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card border shadow-xs h-100" data-testid="deduction-rules-summary-alpha">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Alpha Full Day</p>
                                    <h5 class="mb-0 text-success">{{ $summary['alpha_full_day'] }}</h5>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border border-radius-xl p-3 mb-4 bg-gray-100">
                        <form action="{{ route('deduction_rules.index') }}" method="GET" class="row g-3 align-items-end" data-testid="deduction-rules-filter-form">
                            <div class="col-lg-5 col-md-6">
                                <label for="search" class="form-label">Cari Aturan</label>
                                <div class="input-group">
                                    <span class="input-group-text text-body"><i class="fas fa-search" aria-hidden="true"></i></span>
                                    <input type="text" id="search" name="search" class="form-control" value="{{ $search }}" placeholder="Cari tipe karyawan, rate, jam kerja, hari kerja, atau toleransi" data-testid="deduction-rules-search-input">
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <label for="salary_type" class="form-label">Tipe Karyawan</label>
                                <select name="salary_type" id="salary_type" class="form-control" data-testid="deduction-rules-salary-type-filter">
                                    <option value="">Semua Tipe</option>
                                    @foreach ($salaryTypes as $salaryTypeValue => $salaryTypeLabel)
                                        <option value="{{ $salaryTypeValue }}" @selected($selectedSalaryType === $salaryTypeValue)>{{ $salaryTypeLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label for="rate_type" class="form-label">Tipe Rate</label>
                                <select name="rate_type" id="rate_type" class="form-control" data-testid="deduction-rules-rate-type-filter">
                                    <option value="">Semua Rate</option>
                                    @foreach ($rateTypes as $rateTypeValue => $rateTypeLabel)
                                        <option value="{{ $rateTypeValue }}" @selected($selectedRateType === $rateTypeValue)>{{ $rateTypeLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <div class="d-flex gap-2 justify-content-lg-end mt-lg-4">
                                    <button type="submit" class="btn bg-gradient-dark mb-0" data-testid="btn-apply-deduction-filter"><i class="fas fa-filter me-1"></i> Terapkan</button>
                                    <a href="{{ route('deduction_rules.index') }}" class="btn btn-light mb-0" data-testid="btn-reset-deduction-filter">Reset</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                @if ($rules->isEmpty())
                    <div class="text-center py-5" data-testid="{{ $hasActiveDeductionFilters ? 'deduction-rules-filter-empty-state' : 'deduction-rules-empty-state' }}">
                        <i class="fas fa-percent fa-3x text-secondary mb-3"></i>
                        @if ($hasActiveDeductionFilters)
                            <p class="text-secondary mb-1">Tidak ada aturan potongan yang cocok dengan pencarian atau filter saat ini.</p>
                            <p class="text-sm text-secondary mb-3">Coba ubah tipe karyawan, tipe rate, atau kata kunci untuk melihat hasil lain.</p>
                            <a href="{{ route('deduction_rules.index') }}" class="btn btn-light btn-sm mb-0">Reset Filter</a>
                        @else
                            <p class="text-secondary mb-0">Belum ada aturan potongan untuk tenant ini.</p>
                        @endif
                    </div>
                @else
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0" data-testid="deduction-rules-table">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4 text-start">Aturan</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Jam/Hari</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Hari/Bulan</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Toleransi</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Tipe Rate</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Alpha Penuh</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rules as $rule)
                                    <tr>
                                        <td class="ps-4 text-start">
                                            <div>
                                                <h6 class="mb-0 text-sm">Potongan {{ $salaryTypes[$rule->salary_type] ?? ucfirst($rule->salary_type) }}</h6>
                                                <p class="text-xs text-secondary mb-0 mt-1">ID #{{ $rule->id }} • {{ $rateTypes[$rule->rate_type] ?? ucfirst($rule->rate_type) }}</p>
                                            </div>
                                        </td>
                                        <td class="text-center"><span class="text-sm font-weight-bold">{{ $rule->working_hours_per_day }} jam</span></td>
                                        <td class="text-center"><span class="text-secondary text-sm">{{ $rule->working_days_per_month }} hari</span></td>
                                        <td class="text-center"><span class="text-secondary text-sm">{{ $rule->tolerance_minutes }} menit</span></td>
                                        <td class="text-center">
                                            <span class="badge {{ $rule->rate_type === 'proportional' ? 'bg-gradient-info' : 'bg-gradient-secondary' }}">{{ $rateTypes[$rule->rate_type] ?? ucfirst($rule->rate_type) }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $rule->alpha_full_day ? 'bg-gradient-success' : 'bg-gradient-secondary' }}">{{ $rule->alpha_full_day ? 'Ya' : 'Tidak' }}</span>
                                        </td>
                                        <td class="text-start">
                                            <div class="d-flex align-items-center gap-3">
                                                <a href="{{ route('deduction_rules.edit', $rule) }}" class="mx-1" data-bs-toggle="tooltip" title="Edit" data-testid="btn-edit-deduction-rule-{{ $rule->id }}">
                                                    <i class="fas fa-edit text-secondary"></i>
                                                </a>
                                                <button type="button" class="border-0 bg-transparent p-0 mx-1" data-bs-toggle="modal" data-bs-target="#deleteDeductionRuleModal-{{ $rule->id }}" title="Hapus" data-testid="btn-delete-deduction-rule-{{ $rule->id }}">
                                                    <i class="fas fa-trash text-danger"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <div class="modal fade" id="deleteDeductionRuleModal-{{ $rule->id }}" tabindex="-1" aria-labelledby="deleteDeductionRuleModalLabel-{{ $rule->id }}" aria-hidden="true" data-testid="deduction-rule-delete-modal-{{ $rule->id }}">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="deleteDeductionRuleModalLabel-{{ $rule->id }}">Konfirmasi Hapus Aturan Potongan</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Apakah Anda yakin ingin menghapus aturan <strong>Potongan {{ $salaryTypes[$rule->salary_type] ?? ucfirst($rule->salary_type) }}</strong>?
                                                </div>
                                                <div class="modal-footer">
                                                    <form action="{{ route('deduction_rules.destroy', $rule) }}" method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button" class="btn btn-light mb-0" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i> Batal</button>
                                                        <button type="submit" class="btn btn-danger mb-0" data-testid="confirm-delete-deduction-rule-{{ $rule->id }}"><i class="fas fa-trash me-1"></i> Hapus</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 pt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <p class="text-sm text-secondary mb-0">Menampilkan {{ $rules->count() }} dari total {{ $rules->total() }} aturan potongan.</p>
                        {{ $rules->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addDeductionRuleIndexModal" tabindex="-1" aria-labelledby="addDeductionRuleIndexModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="addDeductionRuleIndexModalLabel">Tambah Aturan Potongan</h5>
                    <p class="text-sm text-secondary mb-0">Lengkapi tipe karyawan, jam kerja, dan skema rate untuk menambah aturan langsung dari halaman daftar.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('deduction_rules.store') }}" method="POST" data-testid="deduction-rules-index-create-form" autocomplete="off">
                    @csrf
                    <input type="text" class="d-none" tabindex="-1" autocomplete="username">
                    <input type="password" class="d-none" tabindex="-1" autocomplete="new-password">
                    @include('deduction_rules._form', ['deductionRule' => $rule, 'forceBlankDefaults' => true, 'disableAutofill' => true])
                    <div class="d-flex justify-content-end gap-2 mt-4 flex-wrap">
                        <button type="button" class="btn btn-light mb-0" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i> Batal</button>
                        <a href="{{ route('deduction_rules.create') }}" class="btn btn-outline-dark mb-0"><i class="fas fa-up-right-from-square me-1"></i> Halaman Penuh</a>
                        <button type="submit" class="btn bg-gradient-primary mb-0"><i class="fas fa-save me-1"></i> Simpan Aturan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@php($hasCreateDeductionRuleErrors = $errors->has('salary_type') || $errors->has('working_hours_per_day') || $errors->has('working_days_per_month') || $errors->has('tolerance_minutes') || $errors->has('rate_type') || $errors->has('alpha_full_day'))

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modalElement = document.getElementById('addDeductionRuleIndexModal');
            var formElement = modalElement ? modalElement.querySelector('[data-testid="deduction-rules-index-create-form"]') : null;
            var hasCreateDeductionRuleErrors = @json($hasCreateDeductionRuleErrors);

            if (!modalElement || !formElement) {
                return;
            }

            var resetCreateForm = function () {
                if (hasCreateDeductionRuleErrors) {
                    return;
                }

                formElement.reset();

                var salaryTypeSelect = formElement.querySelector('[data-testid="deduction-rule-salary-type-select"]');
                var rateTypeSelect = formElement.querySelector('[data-testid="deduction-rule-rate-type-select"]');
                var hoursInput = formElement.querySelector('[data-testid="deduction-rule-hours-input"]');
                var daysInput = formElement.querySelector('[data-testid="deduction-rule-days-input"]');
                var toleranceInput = formElement.querySelector('[data-testid="deduction-rule-tolerance-input"]');
                var alphaSwitch = formElement.querySelector('[data-testid="deduction-rule-alpha-switch"]');

                if (salaryTypeSelect) {
                    salaryTypeSelect.value = 'monthly';
                }

                if (rateTypeSelect) {
                    rateTypeSelect.value = 'proportional';
                }

                if (hoursInput) {
                    hoursInput.value = 8;
                }

                if (daysInput) {
                    daysInput.value = 22;
                }

                if (toleranceInput) {
                    toleranceInput.value = 15;
                }

                if (alphaSwitch) {
                    alphaSwitch.checked = true;
                }
            };

            modalElement.addEventListener('show.bs.modal', resetCreateForm);
            modalElement.addEventListener('shown.bs.modal', resetCreateForm);
        });
    </script>
@endpush

@if ($hasCreateDeductionRuleErrors)
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var modalElement = document.getElementById('addDeductionRuleIndexModal');

                if (modalElement && typeof bootstrap !== 'undefined') {
                    bootstrap.Modal.getOrCreateInstance(modalElement).show();
                }
            });
        </script>
    @endpush
@endif

@endsection
