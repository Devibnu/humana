@php
    $selectedTenantId = old('tenant_id', $policy->tenant_id);
    $selectedEmploymentType = old('employment_type', $policy->employment_type);
    $selectedStatus = old('status', $policy->status ?? 'active');
    $components = old('components', $componentDefaults);
    $methodLabels = [
        'flat' => 'Flat',
        'daily_attendance' => 'Harian berbasis kehadiran',
        'deduct_per_leave_day' => 'Potong per hari cuti',
        'base_salary' => 'Proporsional dari gaji pokok',
        'from_payroll_allowance' => 'Dari template payroll',
    ];
@endphp

<style>
    .policy-builder {
        background: #f7f9fc;
        border: 1px solid #e9ecef;
        border-radius: 14px;
        overflow: hidden;
    }

    .policy-tabs {
        background: #fff;
        border-bottom: 1px solid #edf0f4;
        gap: .5rem;
        padding: 1rem 1rem 0;
    }

    .policy-tabs .nav-link {
        align-items: center;
        border: 1px solid transparent;
        border-radius: 10px 10px 0 0;
        color: #67748e;
        display: flex;
        gap: .625rem;
        min-height: 3.25rem;
        padding: .75rem 1rem;
    }

    .policy-tabs .nav-link.active {
        background: #f7f9fc;
        border-color: #edf0f4 #edf0f4 #f7f9fc;
        color: #344767;
        font-weight: 700;
    }

    .policy-tab-number {
        align-items: center;
        background: #e9ecef;
        border-radius: 999px;
        color: #344767;
        display: inline-flex;
        font-size: .75rem;
        font-weight: 800;
        height: 1.75rem;
        justify-content: center;
        width: 1.75rem;
    }

    .policy-tabs .nav-link.active .policy-tab-number {
        background: #344767;
        color: #fff;
    }

    .policy-tab-content {
        padding: 1.25rem;
    }

    .policy-panel {
        background: #fff;
        border: 1px solid #edf0f4;
        border-radius: 12px;
        padding: 1.25rem;
    }

    .policy-panel-header {
        align-items: flex-start;
        display: flex;
        gap: 1rem;
        justify-content: space-between;
        margin-bottom: 1rem;
    }

    .policy-component {
        background: #fff;
        border: 1px solid #e9ecef !important;
        border-radius: 10px !important;
        margin-bottom: .625rem;
        overflow: hidden;
    }

    .policy-component-toggle {
        background: #fff;
        box-shadow: none !important;
        color: #344767;
        padding: .75rem .875rem;
    }

    .policy-component-toggle:not(.collapsed) {
        background: #fbfcfe;
        color: #344767;
    }

    .policy-component-summary {
        align-items: center;
        display: grid;
        gap: .5rem;
        grid-template-columns: minmax(0, 1fr) auto auto;
        padding-right: .75rem;
        width: 100%;
    }

    .policy-component-name {
        font-size: .9rem;
        font-weight: 700;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .policy-component-amount {
        color: #344767;
        font-size: .8rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .policy-component-body {
        background: #fff;
        padding: .875rem;
    }

    .policy-chip {
        background: #f2f4f7;
        border-radius: 999px;
        color: #344767;
        display: inline-flex;
        font-size: .7rem;
        font-weight: 700;
        padding: .35rem .65rem;
    }

    .policy-template {
        border: 1px solid #d8dee9;
        border-radius: 10px;
        color: #344767;
        min-height: 100%;
        text-align: left;
        white-space: normal;
    }

    .policy-template:hover {
        border-color: #cb0c9f;
        color: #cb0c9f;
    }

    .policy-review-item {
        background: #f8fafc;
        border: 1px solid #edf0f4;
        border-radius: 10px;
        padding: .875rem;
    }

    .policy-simulation {
        background: #fff;
        border: 1px solid #dfe5ee;
        border-radius: 10px;
        padding: .875rem;
    }

    .policy-simulation-row {
        align-items: center;
        border-bottom: 1px solid #edf0f4;
        display: flex;
        justify-content: space-between;
        padding: .45rem 0;
    }

    .policy-simulation-row:last-child {
        border-bottom: 0;
    }

    .policy-actions {
        background: rgba(255, 255, 255, .96);
        border-top: 1px solid #edf0f4;
        bottom: 0;
        padding: 1rem 1.25rem;
        position: sticky;
        z-index: 2;
    }

    @media (max-width: 767.98px) {
        .policy-tabs {
            padding: .75rem .75rem 0;
        }

        .policy-tabs .nav-link {
            border-radius: 10px;
            justify-content: flex-start;
            width: 100%;
        }

        .policy-tab-content {
            padding: .875rem;
        }

        .policy-panel {
            padding: 1rem;
        }

        .policy-panel-header {
            flex-direction: column;
        }

        .policy-component-summary {
            grid-template-columns: minmax(0, 1fr);
        }

        .policy-actions {
            padding: .875rem;
        }
    }
</style>

<div class="policy-builder">
    <ul class="nav nav-tabs policy-tabs" id="policyBuilderTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="policy-info-tab" data-bs-toggle="tab" data-bs-target="#policy-info-pane" type="button" role="tab" aria-controls="policy-info-pane" aria-selected="true">
                <span class="policy-tab-number">1</span>
                <span>
                    <span class="d-block">Informasi Policy</span>
                    <span class="d-block text-xs font-weight-normal">Tenant dan sasaran karyawan</span>
                </span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="policy-components-tab" data-bs-toggle="tab" data-bs-target="#policy-components-pane" type="button" role="tab" aria-controls="policy-components-pane" aria-selected="false">
                <span class="policy-tab-number">2</span>
                <span>
                    <span class="d-block">Komponen Payroll</span>
                    <span class="d-block text-xs font-weight-normal">Tunjangan, potongan, cuti</span>
                </span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="policy-review-tab" data-bs-toggle="tab" data-bs-target="#policy-review-pane" type="button" role="tab" aria-controls="policy-review-pane" aria-selected="false">
                <span class="policy-tab-number">3</span>
                <span>
                    <span class="d-block">Review & Simpan</span>
                    <span class="d-block text-xs font-weight-normal">Cek ringkasan sebelum simpan</span>
                </span>
            </button>
        </li>
    </ul>

    <div class="tab-content policy-tab-content">
        <div class="tab-pane fade show active" id="policy-info-pane" role="tabpanel" aria-labelledby="policy-info-tab" tabindex="0">
            <div class="policy-panel">
                <div class="policy-panel-header">
                    <div>
                        <h6 class="mb-1">Informasi Policy</h6>
                        <p class="text-sm text-secondary mb-0">Isi bagian ini dulu agar sistem tahu policy berlaku untuk karyawan yang mana.</p>
                    </div>
                    <span class="policy-chip">Langkah 1</span>
                </div>

                <div class="row g-3">
                    <div class="col-lg-6">
                        <label class="form-label">Perusahaan / Tenant <span class="text-danger">*</span></label>
                        <select name="tenant_id" class="form-control @error('tenant_id') is-invalid @enderror" required data-review-source="Tenant">
                            <option value="">Pilih tenant</option>
                            @foreach($tenants as $tenant)
                                <option value="{{ $tenant->id }}" @selected((string) $selectedTenantId === (string) $tenant->id)>{{ $tenant->name }}</option>
                            @endforeach
                        </select>
                        @error('tenant_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-lg-6">
                        <label class="form-label">Nama Policy <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $policy->name) }}" placeholder="Contoh: Payroll Staff Tetap" list="policy-name-suggestions" required data-review-source="Nama policy">
                        <datalist id="policy-name-suggestions">
                            <option value="Payroll Staff Tetap">
                            <option value="Payroll Karyawan Kontrak">
                            <option value="Payroll Operator Produksi">
                            <option value="Payroll Supervisor">
                            <option value="Payroll Harian">
                        </datalist>
                        <p class="text-xs text-secondary mt-1 mb-0">Gunakan nama policy yang mudah dikenali HR/payroll.</p>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-lg-6">
                        <label class="form-label">Status Karyawan</label>
                        <div class="btn-group w-100" role="group" aria-label="Status karyawan">
                            <input type="radio" class="btn-check" name="employment_type" id="employment_all" value="" @checked($selectedEmploymentType === null || $selectedEmploymentType === '') data-review-source="Status karyawan">
                            <label class="btn btn-outline-secondary mb-0" for="employment_all">Semua</label>
                            <input type="radio" class="btn-check" name="employment_type" id="employment_tetap" value="tetap" @checked($selectedEmploymentType === 'tetap') data-review-source="Status karyawan">
                            <label class="btn btn-outline-secondary mb-0" for="employment_tetap">Tetap</label>
                            <input type="radio" class="btn-check" name="employment_type" id="employment_kontrak" value="kontrak" @checked($selectedEmploymentType === 'kontrak') data-review-source="Status karyawan">
                            <label class="btn btn-outline-secondary mb-0" for="employment_kontrak">Kontrak</label>
                        </div>
                        @error('employment_type')<div class="text-danger text-xs mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-lg-6">
                        <label class="form-label">Grade / Level</label>
                        <select name="employee_level_code" class="form-control @error('employee_level_code') is-invalid @enderror" data-review-source="Grade / Level">
                            <option value="">Semua grade</option>
                            @foreach($levels as $level)
                                <option value="{{ $level->code }}" @selected(old('employee_level_code', $policy->employee_level_code) === $level->code)>{{ $level->name }}</option>
                            @endforeach
                        </select>
                        @error('employee_level_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-lg-6">
                        <label class="form-label">Jabatan</label>
                        <select name="position_id" class="form-control @error('position_id') is-invalid @enderror" data-review-source="Jabatan">
                            <option value="">Semua jabatan</option>
                            @foreach($positions as $position)
                                <option value="{{ $position->id }}" @selected((string) old('position_id', $policy->position_id) === (string) $position->id)>{{ $position->name }}</option>
                            @endforeach
                        </select>
                        @error('position_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-lg-3">
                        <label class="form-label">Prioritas</label>
                        <input type="number" name="priority" class="form-control @error('priority') is-invalid @enderror" value="{{ old('priority', $policy->priority ?? 100) }}" min="1" max="999" required data-review-source="Prioritas">
                        <p class="text-xs text-secondary mt-1 mb-0">Angka kecil dipakai lebih dulu.</p>
                        @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-lg-3">
                        <label class="form-label">Status Policy</label>
                        <select name="status" class="form-control @error('status') is-invalid @enderror" required data-review-source="Status policy">
                            <option value="active" @selected($selectedStatus === 'active')>Aktif</option>
                            <option value="inactive" @selected($selectedStatus === 'inactive')>Tidak Aktif</option>
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="policy-components-pane" role="tabpanel" aria-labelledby="policy-components-tab" tabindex="0">
            <div class="policy-panel">
                <div class="policy-panel-header">
                    <div>
                        <h6 class="mb-1">Komponen Payroll</h6>
                        <p class="text-sm text-secondary mb-0">Buka komponen yang ingin diatur. Detail disembunyikan agar form tetap ringkas.</p>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm mb-0" data-add-component>
                        <i class="fas fa-plus me-1"></i> Tambah Komponen
                    </button>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <button type="button" class="btn policy-template w-100 mb-0" data-template="meal">
                            <span class="d-block font-weight-bold">Uang Makan</span>
                            <span class="d-block text-xs text-secondary">Dikali hari masuk, cuti bisa dipotong.</span>
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn policy-template w-100 mb-0" data-template="transport">
                            <span class="d-block font-weight-bold">Transport</span>
                            <span class="d-block text-xs text-secondary">Nominal tetap setiap periode payroll.</span>
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn policy-template w-100 mb-0" data-template="deduction">
                            <span class="d-block font-weight-bold">Potongan Cuti</span>
                            <span class="d-block text-xs text-secondary">Untuk potongan per hari unpaid leave.</span>
                        </button>
                    </div>
                </div>

                <div class="accordion" data-components-list>
                    @foreach($components as $index => $component)
                        @include('payroll.policies._component-row', ['index' => $index, 'component' => $component, 'methodLabels' => $methodLabels])
                    @endforeach
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="policy-review-pane" role="tabpanel" aria-labelledby="policy-review-tab" tabindex="0">
            <div class="policy-panel">
                <div class="policy-panel-header">
                    <div>
                        <h6 class="mb-1">Review Policy</h6>
                        <p class="text-sm text-secondary mb-0">Pastikan sasaran dan komponen sudah sesuai sebelum disimpan.</p>
                    </div>
                    <span class="policy-chip">Langkah 3</span>
                </div>

                <div class="row g-3">
                    <div class="col-lg-5">
                        <div class="policy-review-item h-100">
                            <h6 class="mb-3">Ringkasan sasaran</h6>
                            <div class="d-flex justify-content-between gap-3 mb-2">
                                <span class="text-sm text-secondary">Nama</span>
                                <strong class="text-sm text-end" data-review-value="Nama policy">-</strong>
                            </div>
                            <div class="d-flex justify-content-between gap-3 mb-2">
                                <span class="text-sm text-secondary">Tenant</span>
                                <strong class="text-sm text-end" data-review-value="Tenant">-</strong>
                            </div>
                            <div class="d-flex justify-content-between gap-3 mb-2">
                                <span class="text-sm text-secondary">Status karyawan</span>
                                <strong class="text-sm text-end" data-review-value="Status karyawan">Semua</strong>
                            </div>
                            <div class="d-flex justify-content-between gap-3 mb-2">
                                <span class="text-sm text-secondary">Grade</span>
                                <strong class="text-sm text-end" data-review-value="Grade / Level">Semua grade</strong>
                            </div>
                            <div class="d-flex justify-content-between gap-3">
                                <span class="text-sm text-secondary">Jabatan</span>
                                <strong class="text-sm text-end" data-review-value="Jabatan">Semua jabatan</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="policy-review-item h-100">
                            <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                                <h6 class="mb-0">Komponen aktif</h6>
                                <span class="policy-chip" data-components-count>0 komponen</span>
                            </div>
                            <div data-components-review></div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="policy-simulation">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                <div>
                                    <h6 class="mb-1">Simulasi Policy</h6>
                                    <p class="text-xs text-secondary mb-0">Estimasi cepat dengan contoh kehadiran 22 hari.</p>
                                </div>
                                <span class="policy-chip">Hadir: <span class="ms-1" data-simulation-days>22</span> hari</span>
                            </div>
                            <div data-policy-simulation></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="policy-actions d-flex justify-content-between flex-wrap gap-2">
        <button type="button" class="btn btn-light mb-0" data-prev-tab>
            <i class="fas fa-arrow-left me-1"></i> Sebelumnya
        </button>
        <div class="d-flex gap-2">
            <a href="{{ $policy->exists ? route('payroll.policies.index', ['tenant_id' => $policy->tenant_id]) : route('payroll.policies.index') }}" class="btn btn-light mb-0">Batal</a>
            <button type="button" class="btn btn-outline-primary mb-0" data-next-tab>
                Lanjut <i class="fas fa-arrow-right ms-1"></i>
            </button>
            <button type="submit" class="btn bg-gradient-primary mb-0 d-none" data-submit-policy>
                <i class="fas fa-save me-1"></i> {{ $policy->exists ? 'Simpan Perubahan' : 'Simpan Policy' }}
            </button>
        </div>
    </div>
</div>

<template data-component-template>
    @include('payroll.policies._component-row', ['index' => '__INDEX__', 'component' => [
        'component_code' => '',
        'component_name' => '',
        'component_type' => 'earning',
        'amount' => 0,
        'calculation_method' => 'flat',
        'leave_paid_behavior' => 'keep',
        'leave_unpaid_behavior' => 'deduct_daily',
        'sort_order' => 10,
    ], 'methodLabels' => $methodLabels])
</template>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const list = document.querySelector('[data-components-list]');
        const template = document.querySelector('[data-component-template]');
        const tabButtons = Array.from(document.querySelectorAll('#policyBuilderTabs [data-bs-toggle="tab"]'));
        const prevButton = document.querySelector('[data-prev-tab]');
        const nextButton = document.querySelector('[data-next-tab]');
        const submitButton = document.querySelector('[data-submit-policy]');
        const simulationDays = 22;
        const typeLabels = {
            earning: 'Tunjangan',
            deduction: 'Potongan',
            bonus: 'Bonus'
        };
        const presets = {
            meal: {
                component_code: 'meal_allowance',
                component_name: 'Uang Makan',
                component_type: 'earning',
                amount: 0,
                calculation_method: 'daily_attendance',
                leave_paid_behavior: 'deduct_daily',
                leave_unpaid_behavior: 'deduct_daily',
                notes: 'Dihitung dari jumlah hari masuk.'
            },
            transport: {
                component_code: 'transport_allowance',
                component_name: 'Tunjangan Transport',
                component_type: 'earning',
                amount: 0,
                calculation_method: 'flat',
                leave_paid_behavior: 'keep',
                leave_unpaid_behavior: 'keep',
                notes: 'Nominal tetap setiap periode payroll.'
            },
            deduction: {
                component_code: 'unpaid_leave',
                component_name: 'Potongan Unpaid Leave',
                component_type: 'deduction',
                amount: 0,
                calculation_method: 'deduct_per_leave_day',
                leave_paid_behavior: 'keep',
                leave_unpaid_behavior: 'deduct_daily',
                notes: 'Potongan dihitung per hari unpaid leave.'
            }
        };

        const activeTabIndex = function () {
            return tabButtons.findIndex(function (button) {
                return button.classList.contains('active');
            });
        };

        const showTab = function (index) {
            const button = tabButtons[index];
            if (!button) {
                return;
            }

            if (window.bootstrap && window.bootstrap.Tab) {
                window.bootstrap.Tab.getOrCreateInstance(button).show();
            } else {
                button.click();
            }
        };

        const updateTabActions = function () {
            const index = activeTabIndex();
            prevButton.classList.toggle('invisible', index === 0);
            nextButton.classList.toggle('d-none', index === tabButtons.length - 1);
            submitButton.classList.toggle('d-none', index !== tabButtons.length - 1);
            updateReview();
        };

        const nextIndex = function () {
            return list.querySelectorAll('[data-component-row]').length;
        };

        const moneyText = function (value) {
            const number = Number(value || 0);
            return 'Rp ' + number.toLocaleString('id-ID');
        };

        const numericText = function (value) {
            return String(value || '').replace(/[^\d]/g, '');
        };

        const slugText = function (value) {
            return String(value || '')
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]+/g, '_')
                .replace(/^_+|_+$/g, '')
                .substring(0, 80);
        };

        const formatRupiahField = function (field) {
            const amount = numericText(field.value);
            field.value = moneyText(amount);
            const hidden = field.closest('[data-component-row]')?.querySelector('[data-amount-value]');
            if (hidden) {
                hidden.value = amount || 0;
            }
        };

        const componentAmount = function (row) {
            return Number(row.querySelector('[data-amount-value]')?.value || 0);
        };

        const simulatedAmount = function (row) {
            const amount = componentAmount(row);
            const method = row.querySelector('[name$="[calculation_method]"]')?.value || 'flat';

            if (method === 'daily_attendance') {
                return amount * simulationDays;
            }

            return amount;
        };

        const selectedText = function (field) {
            if (!field) {
                return '-';
            }

            if (field.type === 'radio') {
                const selected = document.querySelector('[name="' + field.name + '"]:checked');
                if (!selected || selected.value === '') {
                    return 'Semua';
                }
                return selected.nextElementSibling ? selected.nextElementSibling.textContent.trim() : selected.value;
            }

            if (field.tagName === 'SELECT') {
                return field.selectedOptions[0]?.textContent.trim() || '-';
            }

            return field.value || '-';
        };

        const updateReview = function () {
            document.querySelectorAll('[data-review-value]').forEach(function (target) {
                const label = target.dataset.reviewValue;
                const field = document.querySelector('[data-review-source="' + label + '"]');
                target.textContent = selectedText(field);
            });

            const rows = Array.from(list.querySelectorAll('[data-component-row]'));
            const activeRows = rows.filter(function (row) {
                const code = row.querySelector('[name$="[component_code]"]')?.value.trim();
                const name = row.querySelector('[data-component-name]')?.value.trim();
                return code || name;
            });
            const review = document.querySelector('[data-components-review]');
            const count = document.querySelector('[data-components-count]');
            count.textContent = activeRows.length + ' komponen';

            if (activeRows.length === 0) {
                review.innerHTML = '<p class="text-sm text-secondary mb-0">Belum ada komponen yang diisi.</p>';
                document.querySelector('[data-policy-simulation]').innerHTML = '<p class="text-sm text-secondary mb-0">Belum ada komponen untuk disimulasikan.</p>';
                return;
            }

            review.innerHTML = activeRows.map(function (row) {
                const name = row.querySelector('[data-component-name]')?.value || 'Komponen baru';
                const type = row.querySelector('[name$="[component_type]"]')?.selectedOptions[0]?.textContent.trim() || '-';
                const method = row.querySelector('[name$="[calculation_method]"]')?.selectedOptions[0]?.textContent.trim() || '-';
                const amount = componentAmount(row);
                return '<div class="d-flex justify-content-between gap-3 border-bottom py-2">' +
                    '<div><strong class="text-sm d-block">' + name + '</strong><span class="text-xs text-secondary">' + type + ' - ' + method + '</span></div>' +
                    '<strong class="text-sm text-end">' + moneyText(amount) + '</strong>' +
                    '</div>';
            }).join('');

            const simulation = document.querySelector('[data-policy-simulation]');
            simulation.innerHTML = activeRows.map(function (row) {
                const name = row.querySelector('[data-component-name]')?.value || 'Komponen baru';
                const type = row.querySelector('[name$="[component_type]"]')?.value || 'earning';
                const amount = simulatedAmount(row);
                const sign = type === 'deduction' ? '-' : '';

                return '<div class="policy-simulation-row">' +
                    '<span class="text-sm text-secondary">' + name + '</span>' +
                    '<strong class="text-sm">' + sign + moneyText(amount) + '</strong>' +
                    '</div>';
            }).join('') || '<p class="text-sm text-secondary mb-0">Belum ada komponen untuk disimulasikan.</p>';
        };

        const refreshTitles = function () {
            list.querySelectorAll('[data-component-row]').forEach(function (row, rowIndex) {
                const nameInput = row.querySelector('[data-component-name]');
                const title = row.querySelector('[data-component-title]');
                const orderInput = row.querySelector('[data-sort-order]');
                const codeInput = row.querySelector('[data-component-code]');
                const typeInput = row.querySelector('[data-component-type]');
                const typeChip = row.querySelector('[data-component-type-chip]');
                const amountLabel = row.querySelector('[data-component-amount-label]');
                const methodInput = row.querySelector('[data-calculation-method]');
                const methodHelper = row.querySelector('[data-method-helper]');
                title.textContent = nameInput.value || 'Komponen baru';
                if (codeInput) {
                    codeInput.value = slugText(nameInput.value);
                }
                if (typeChip && typeInput) {
                    typeChip.textContent = typeLabels[typeInput.value] || 'Tunjangan';
                }
                if (amountLabel) {
                    amountLabel.textContent = moneyText(componentAmount(row));
                }
                if (methodInput && methodHelper) {
                    methodHelper.textContent = methodInput.selectedOptions[0]?.dataset.helper || '';
                }
                if (!orderInput.value || orderInput.value === '0') {
                    orderInput.value = (rowIndex + 1) * 10;
                }
            });
            updateReview();
        };

        const applyPreset = function (row, preset) {
            Object.keys(preset).forEach(function (key) {
                const field = row.querySelector('[name$="[' + key + ']"]');
                if (field) {
                    field.value = preset[key];
                }
            });
            const amountField = row.querySelector('[data-rupiah-input]');
            if (amountField) {
                amountField.value = moneyText(preset.amount || 0);
            }
            refreshTitles();
        };

        const addRow = function (preset) {
            const wrapper = document.createElement('div');
            wrapper.innerHTML = template.innerHTML.replaceAll('__INDEX__', String(Date.now()));
            const row = wrapper.firstElementChild;
            list.appendChild(row);
            if (preset) {
                applyPreset(row, preset);
            }
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return row;
        };

        document.querySelector('[data-add-component]')?.addEventListener('click', function () {
            addRow();
        });

        document.querySelectorAll('[data-template]').forEach(function (button) {
            button.addEventListener('click', function () {
                addRow(presets[button.dataset.template]);
            });
        });

        nextButton.addEventListener('click', function () {
            showTab(activeTabIndex() + 1);
        });

        prevButton.addEventListener('click', function () {
            showTab(activeTabIndex() - 1);
        });

        tabButtons.forEach(function (button) {
            button.addEventListener('shown.bs.tab', updateTabActions);
            button.addEventListener('click', function () {
                setTimeout(updateTabActions, 0);
            });
        });

        document.addEventListener('input', function (event) {
            if (event.target.matches('[data-rupiah-input]')) {
                formatRupiahField(event.target);
                refreshTitles();
            }

            if (event.target.matches('[data-component-name], [data-review-source], [data-amount-value]')) {
                refreshTitles();
            }
        });

        document.addEventListener('change', function (event) {
            if (event.target.matches('[data-review-source], [name^="components"]')) {
                refreshTitles();
            }
        });

        list.addEventListener('click', function (event) {
            const removeButton = event.target.closest('[data-remove-component]');
            if (!removeButton) {
                return;
            }
            const rows = list.querySelectorAll('[data-component-row]');
            if (rows.length === 1) {
                const row = rows[0];
                row.querySelectorAll('input[type="text"], input[type="number"]').forEach(function (input) {
                    input.value = '';
                });
                row.querySelector('[data-rupiah-input]').value = 'Rp 0';
                row.querySelector('[data-amount-value]').value = 0;
                row.querySelector('[data-component-code]').value = '';
                row.querySelector('[name$="[component_type]"]').value = 'earning';
                row.querySelector('[name$="[calculation_method]"]').value = 'flat';
                row.querySelector('[name$="[leave_paid_behavior]"]').value = 'keep';
                row.querySelector('[name$="[leave_unpaid_behavior]"]').value = 'deduct_daily';
                refreshTitles();
                return;
            }
            removeButton.closest('[data-component-row]').remove();
            refreshTitles();
        });

        refreshTitles();
        updateTabActions();
    });
</script>
