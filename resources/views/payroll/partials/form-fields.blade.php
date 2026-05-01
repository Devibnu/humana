<div class="row g-4">
    <div class="col-xl-8">
        <div class="card border shadow-xs mb-4" data-testid="payroll-form-context-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                    <div>
                        <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Konteks Payroll</p>
                        <h6 class="mb-1">Pilih karyawan dan aturan potongan yang tepat</h6>
                        <p class="text-sm text-secondary mb-0">Gunakan blok ini untuk menetapkan siapa yang diproses dan rule absensi mana yang akan menjadi dasar perhitungan payroll.</p>
                    </div>
                    <span class="badge bg-gradient-info">Langkah 1</span>
                </div>

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Karyawan</label>
                        <select name="employee_id" class="form-control @error('employee_id') is-invalid @enderror" required data-testid="payroll-employee-select">
                            <option value="">Pilih Karyawan</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" @selected((string) old('employee_id', $payroll?->employee_id) === (string) $employee->id)>
                                    {{ $employee->name }}{{ $employee->employee_code ? ' - '.$employee->employee_code : '' }}{{ $employee->tenant?->name ? ' - '.$employee->tenant->name : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">Tipe rule akan difilter otomatis berdasarkan komponen gaji yang diisi: harian atau bulanan.</small>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Pilih Aturan Potongan Absensi</label>
                        <select name="deduction_rule_id" class="form-control @error('deduction_rule_id') is-invalid @enderror" required data-testid="payroll-deduction-rule-select">
                            <option value="">Pilih aturan potongan</option>
                            @foreach($rules as $rule)
                                <option value="{{ $rule->id }}" data-salary-type="{{ $rule->salary_type }}" @selected((string) old('deduction_rule_id', $payroll?->deduction_rule_id) === (string) $rule->id)>
                                    {{ $rule->salary_type === 'daily' ? 'Harian' : 'Bulanan' }} -
                                    {{ $rule->working_hours_per_day }} jam/hari,
                                    {{ $rule->working_days_per_month }} hari/bulan,
                                    {{ strtoupper($rule->rate_type) }},
                                    Alpha: {{ $rule->alpha_full_day ? 'YA' : 'TIDAK' }}
                                </option>
                            @endforeach
                        </select>
                        @error('deduction_rule_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted" id="deduction-rule-help-text">Pilih aturan sesuai tipe gaji payroll: harian atau bulanan.</small>
                        <div class="text-warning text-xs mt-1 d-none" id="deduction-rule-empty-state">Tidak ada aturan potongan yang cocok dengan tipe gaji yang sedang diisi.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border shadow-xs mb-4" data-testid="payroll-form-compensation-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                    <div>
                        <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Kompensasi</p>
                        <h6 class="mb-1">Isi komponen gaji dan tunjangan</h6>
                        <p class="text-sm text-secondary mb-0">Minimal isi salah satu basis payroll: gaji bulanan atau gaji harian. Tunjangan dipisah agar audit nominal lebih mudah.</p>
                    </div>
                    <span class="badge bg-gradient-primary">Langkah 2</span>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Gaji Bulanan (Rp)</label>
                        <input type="number" name="monthly_salary" class="form-control @error('monthly_salary') is-invalid @enderror" value="{{ old('monthly_salary', $payroll?->monthly_salary) }}" placeholder="7500000" min="0" step="0.01" data-testid="payroll-monthly-salary-input">
                        @error('monthly_salary')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Gaji Harian (Rp)</label>
                        <input type="number" name="daily_wage" class="form-control @error('daily_wage') is-invalid @enderror" value="{{ old('daily_wage', $payroll?->daily_wage) }}" placeholder="300000" min="0" step="0.01" data-testid="payroll-daily-wage-input">
                        @error('daily_wage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Tunjangan Transport (Rp)</label>
                        <input type="number" name="allowance_transport" class="form-control @error('allowance_transport') is-invalid @enderror" value="{{ old('allowance_transport', $payroll?->allowance_transport) }}" placeholder="0" min="0" step="0.01">
                        @error('allowance_transport')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Tunjangan Makan (Rp)</label>
                        <input type="number" name="allowance_meal" class="form-control @error('allowance_meal') is-invalid @enderror" value="{{ old('allowance_meal', $payroll?->allowance_meal) }}" placeholder="0" min="0" step="0.01">
                        @error('allowance_meal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Tunjangan Kesehatan (Rp)</label>
                        <input type="number" name="allowance_health" class="form-control @error('allowance_health') is-invalid @enderror" value="{{ old('allowance_health', $payroll?->allowance_health) }}" placeholder="0" min="0" step="0.01">
                        @error('allowance_health')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card border shadow-xs" data-testid="payroll-form-deduction-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                    <div>
                        <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Potongan Dan Periode</p>
                        <h6 class="mb-1">Lengkapi periode payroll dan potongan manual</h6>
                        <p class="text-sm text-secondary mb-0">Blok ini menutup proses input payroll dengan potongan tambahan dan rentang periode agar slip payroll lebih mudah dibaca.</p>
                    </div>
                    <span class="badge bg-gradient-dark">Langkah 3</span>
                </div>

                <div class="alert alert-info text-white mb-3" role="alert">
                    Potongan absensi akan dihitung otomatis dari aturan potongan dan periode payroll yang dipilih.
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Potongan Pajak (Rp)</label>
                        <input type="number" name="deduction_tax" class="form-control @error('deduction_tax') is-invalid @enderror" value="{{ old('deduction_tax', $payroll?->deduction_tax) }}" placeholder="0" min="0" step="0.01">
                        @error('deduction_tax')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Potongan BPJS (Rp)</label>
                        <input type="number" name="deduction_bpjs" class="form-control @error('deduction_bpjs') is-invalid @enderror" value="{{ old('deduction_bpjs', $payroll?->deduction_bpjs) }}" placeholder="0" min="0" step="0.01">
                        @error('deduction_bpjs')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Potongan Pinjaman (Rp)</label>
                        <input type="number" name="deduction_loan" class="form-control @error('deduction_loan') is-invalid @enderror" value="{{ old('deduction_loan', $payroll?->deduction_loan) }}" placeholder="0" min="0" step="0.01">
                        @error('deduction_loan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Periode Mulai</label>
                        <input type="date" name="period_start" class="form-control @error('period_start') is-invalid @enderror" value="{{ old('period_start', $payroll?->period_start) }}">
                        @error('period_start')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Periode Selesai</label>
                        <input type="date" name="period_end" class="form-control @error('period_end') is-invalid @enderror" value="{{ old('period_end', $payroll?->period_end) }}">
                        @error('period_end')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card border shadow-xs bg-gray-100" data-testid="payroll-form-guidance-card">
            <div class="card-body">
                <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Panduan Input</p>
                <h6 class="mb-3">Checklist cepat sebelum simpan</h6>

                <div class="d-flex align-items-start gap-3 mb-3">
                    <div class="icon icon-shape icon-sm bg-gradient-info shadow text-center border-radius-md">
                        <i class="fas fa-user-check text-white opacity-10" aria-hidden="true"></i>
                    </div>
                    <div>
                        <p class="text-sm font-weight-bold mb-1">Pastikan karyawan sesuai tenant</p>
                        <p class="text-xs text-secondary mb-0">Nama karyawan, kode, dan tenant tampil langsung di dropdown untuk mengurangi salah pilih.</p>
                    </div>
                </div>

                <div class="d-flex align-items-start gap-3 mb-3">
                    <div class="icon icon-shape icon-sm bg-gradient-primary shadow text-center border-radius-md">
                        <i class="fas fa-wallet text-white opacity-10" aria-hidden="true"></i>
                    </div>
                    <div>
                        <p class="text-sm font-weight-bold mb-1">Isi satu basis gaji lebih dulu</p>
                        <p class="text-xs text-secondary mb-0">Rule potongan akan menyesuaikan otomatis ke payroll harian atau bulanan.</p>
                    </div>
                </div>

                <div class="d-flex align-items-start gap-3 mb-3">
                    <div class="icon icon-shape icon-sm bg-gradient-dark shadow text-center border-radius-md">
                        <i class="fas fa-calendar-alt text-white opacity-10" aria-hidden="true"></i>
                    </div>
                    <div>
                        <p class="text-sm font-weight-bold mb-1">Lengkapi periode payroll</p>
                        <p class="text-xs text-secondary mb-0">Periode membantu perhitungan absensi dan lembur agar catatan payroll lebih akurat.</p>
                    </div>
                </div>

                <div class="border border-radius-md p-3 bg-white">
                    <p class="text-sm font-weight-bold mb-1">Catatan Pengisian</p>
                    <p class="text-sm text-secondary mb-0">Sistem akan menghitung komponen otomatis seperti potongan absensi dan nilai lembur dari data operasional yang sudah disetujui.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const ruleSelect = document.querySelector('[data-testid="payroll-deduction-rule-select"]');
        const monthlySalaryInput = document.querySelector('[data-testid="payroll-monthly-salary-input"]');
        const dailyWageInput = document.querySelector('[data-testid="payroll-daily-wage-input"]');
        const emptyState = document.getElementById('deduction-rule-empty-state');
        const helpText = document.getElementById('deduction-rule-help-text');

        if (!ruleSelect || !monthlySalaryInput || !dailyWageInput) {
            return;
        }

        const originalOptions = Array.from(ruleSelect.options).map((option) => ({
            value: option.value,
            label: option.textContent,
            salaryType: option.dataset.salaryType || '',
            selected: option.selected,
        }));

        const detectSalaryType = () => {
            const monthlyValue = (monthlySalaryInput.value || '').trim();
            const dailyValue = (dailyWageInput.value || '').trim();

            if (dailyValue !== '' && Number(dailyValue) > 0 && (monthlyValue === '' || Number(monthlyValue) === 0)) {
                return 'daily';
            }

            if (monthlyValue !== '' && Number(monthlyValue) > 0 && (dailyValue === '' || Number(dailyValue) === 0)) {
                return 'monthly';
            }

            const selectedOption = ruleSelect.selectedOptions[0];
            return selectedOption?.dataset.salaryType || '';
        };

        const renderRuleOptions = () => {
            const selectedValue = ruleSelect.value;
            const salaryType = detectSalaryType();

            ruleSelect.innerHTML = '';

            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = salaryType === 'daily'
                ? 'Pilih aturan potongan harian'
                : salaryType === 'monthly'
                    ? 'Pilih aturan potongan bulanan'
                    : 'Pilih aturan potongan';
            ruleSelect.appendChild(placeholder);

            const matchingOptions = originalOptions.filter((option) => option.value !== '' && (!salaryType || option.salaryType === salaryType));

            matchingOptions.forEach((option) => {
                const element = document.createElement('option');
                element.value = option.value;
                element.textContent = option.label;
                element.dataset.salaryType = option.salaryType;
                if (option.value === selectedValue) {
                    element.selected = true;
                }
                ruleSelect.appendChild(element);
            });

            if (ruleSelect.selectedIndex <= 0) {
                const exactMatch = matchingOptions.find((option) => option.value === selectedValue);
                if (!exactMatch) {
                    ruleSelect.value = '';
                }
            }

            if (emptyState) {
                emptyState.classList.toggle('d-none', matchingOptions.length > 0 || !salaryType);
            }

            if (helpText) {
                helpText.textContent = salaryType === 'daily'
                    ? 'Menampilkan hanya rule untuk payroll harian.'
                    : salaryType === 'monthly'
                        ? 'Menampilkan hanya rule untuk payroll bulanan.'
                        : 'Pilih aturan sesuai tipe gaji payroll: harian atau bulanan.';
            }
        };

        monthlySalaryInput.addEventListener('input', renderRuleOptions);
        dailyWageInput.addEventListener('input', renderRuleOptions);
        renderRuleOptions();
    })();
</script>
