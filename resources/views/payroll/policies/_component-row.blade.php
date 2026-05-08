@php
    $componentType = $component['component_type'] ?? 'earning';
    $calculationMethod = $component['calculation_method'] ?? 'flat';
    $paidBehavior = $component['leave_paid_behavior'] ?? 'keep';
    $unpaidBehavior = $component['leave_unpaid_behavior'] ?? 'deduct_daily';
    $componentName = $component['component_name'] ?? 'Komponen baru';
    $collapseId = 'policy-component-'.$index;
    $isOpen = (string) $index === '0';
    $typeLabels = [
        'earning' => 'Tunjangan',
        'deduction' => 'Potongan',
        'bonus' => 'Bonus',
    ];
    $methodHelpers = [
        'flat' => 'Nominal tetap setiap periode payroll.',
        'daily_attendance' => 'Dihitung berdasarkan jumlah hari hadir karyawan.',
        'deduct_per_leave_day' => 'Dipotong sesuai jumlah hari cuti yang dipilih.',
        'base_salary' => 'Mengikuti nilai gaji pokok pada payroll.',
        'from_payroll_allowance' => 'Mengambil nominal dari template allowance payroll.',
    ];
@endphp

<div class="accordion-item policy-component" data-component-row>
    <h2 class="accordion-header" id="{{ $collapseId }}-heading">
        <button class="accordion-button {{ $isOpen ? '' : 'collapsed' }} policy-component-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" aria-expanded="{{ $isOpen ? 'true' : 'false' }}" aria-controls="{{ $collapseId }}">
            <span class="policy-component-summary">
                <span class="policy-component-name" data-component-title>{{ $componentName }}</span>
                <span class="policy-chip" data-component-type-chip>{{ $typeLabels[$componentType] ?? 'Tunjangan' }}</span>
                <span class="policy-component-amount" data-component-amount-label>Rp {{ number_format((float) ($component['amount'] ?? 0), 0, ',', '.') }}</span>
            </span>
        </button>
    </h2>

    <div id="{{ $collapseId }}" class="accordion-collapse collapse {{ $isOpen ? 'show' : '' }}" aria-labelledby="{{ $collapseId }}-heading" data-bs-parent="[data-components-list]">
        <div class="accordion-body policy-component-body">
            <input type="hidden" name="components[{{ $index }}][component_code]" value="{{ $component['component_code'] ?? '' }}" data-component-code>

            <div class="row g-2 g-lg-3">
                <div class="col-md-6">
                    <label class="form-label">Nama Komponen</label>
                    <input type="text" name="components[{{ $index }}][component_name]" class="form-control form-control-sm" value="{{ $component['component_name'] ?? '' }}" placeholder="Contoh: Uang Makan" data-component-name>
                    <p class="text-xs text-secondary mt-1 mb-0">Kode sistem dibuat otomatis dari nama komponen.</p>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nominal</label>
                    <input type="text" class="form-control form-control-sm" value="Rp {{ number_format((float) ($component['amount'] ?? 0), 0, ',', '.') }}" placeholder="Rp 0" inputmode="numeric" data-rupiah-input>
                    <input type="hidden" name="components[{{ $index }}][amount]" value="{{ $component['amount'] ?? 0 }}" data-amount-value>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Kategori Komponen</label>
                    <select name="components[{{ $index }}][component_type]" class="form-control form-control-sm" data-component-type>
                        <option value="earning" @selected($componentType === 'earning')>Tunjangan</option>
                        <option value="deduction" @selected($componentType === 'deduction')>Potongan</option>
                        <option value="bonus" @selected($componentType === 'bonus')>Bonus</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Metode Hitung</label>
                    <select name="components[{{ $index }}][calculation_method]" class="form-control form-control-sm" data-calculation-method>
                        @foreach($methodLabels as $methodValue => $methodLabel)
                            <option value="{{ $methodValue }}" @selected($calculationMethod === $methodValue) data-helper="{{ $methodHelpers[$methodValue] ?? '' }}">{{ $methodLabel }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-secondary mt-1 mb-0" data-method-helper>{{ $methodHelpers[$calculationMethod] ?? '' }}</p>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Saat Cuti Dibayar</label>
                    <select name="components[{{ $index }}][leave_paid_behavior]" class="form-control form-control-sm">
                        <option value="keep" @selected($paidBehavior === 'keep')>Tetap dibayar</option>
                        <option value="deduct_daily" @selected($paidBehavior === 'deduct_daily')>Potong per hari</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Saat Cuti Tidak Dibayar</label>
                    <select name="components[{{ $index }}][leave_unpaid_behavior]" class="form-control form-control-sm">
                        <option value="keep" @selected($unpaidBehavior === 'keep')>Tetap dibayar</option>
                        <option value="deduct_daily" @selected($unpaidBehavior === 'deduct_daily')>Potong per hari</option>
                    </select>
                </div>
                <div class="col-lg-9">
                    <label class="form-label">Catatan</label>
                    <input type="text" name="components[{{ $index }}][notes]" class="form-control form-control-sm" value="{{ $component['notes'] ?? '' }}" placeholder="Contoh: uang makan dipotong saat cuti">
                </div>
                <div class="col-7 col-lg-2">
                    <label class="form-label">Urutan</label>
                    <input type="number" name="components[{{ $index }}][sort_order]" class="form-control form-control-sm" value="{{ $component['sort_order'] ?? ($loop->iteration ?? 1) * 10 }}" min="0" data-sort-order>
                </div>
                <div class="col-5 col-lg-1 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-danger btn-sm w-100 mb-0" data-remove-component title="Hapus komponen">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
