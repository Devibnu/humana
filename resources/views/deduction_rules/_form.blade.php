@php
    $forceBlankDefaults = $forceBlankDefaults ?? false;
    $disableAutofill = $disableAutofill ?? false;
    $deductionRule = $deductionRule ?? $rule;
    $selectedSalaryType = old('salary_type', $forceBlankDefaults ? 'monthly' : ($deductionRule->salary_type ?? 'monthly'));
    $selectedRateType = old('rate_type', $forceBlankDefaults ? 'proportional' : ($deductionRule->rate_type ?? 'proportional'));
    $workingHours = old('working_hours_per_day', $forceBlankDefaults ? 8 : ($deductionRule->working_hours_per_day ?? 8));
    $workingDays = old('working_days_per_month', $forceBlankDefaults ? 22 : ($deductionRule->working_days_per_month ?? 22));
    $toleranceMinutes = old('tolerance_minutes', $forceBlankDefaults ? 15 : ($deductionRule->tolerance_minutes ?? 15));
    $alphaFullDay = old('alpha_full_day', $forceBlankDefaults ? true : ($deductionRule->alpha_full_day ?? true));
@endphp

<div class="row">
    <div class="col-md-6">
        <label class="form-label d-flex align-items-center gap-1" for="salary_type">
            Tipe Karyawan
            <i class="fas fa-info-circle text-secondary text-xs" data-bs-toggle="tooltip" title="Pilih kategori gaji karyawan yang mengikuti aturan potongan ini"></i>
        </label>
        <select name="salary_type" id="salary_type" class="form-control @error('salary_type') is-invalid @enderror" required data-testid="deduction-rule-salary-type-select" @if($disableAutofill) autocomplete="off" @endif>
            @foreach ($salaryTypes as $salaryTypeValue => $salaryTypeLabel)
                <option value="{{ $salaryTypeValue }}" @selected($selectedSalaryType === $salaryTypeValue)>{{ $salaryTypeLabel }}</option>
            @endforeach
        </select>
        @error('salary_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="text-muted">Aturan akan diterapkan sesuai basis gaji harian atau bulanan.</small>
    </div>

    <div class="col-md-6">
        <label class="form-label d-flex align-items-center gap-1" for="working_hours_per_day">
            Jam Kerja per Hari
            <i class="fas fa-info-circle text-secondary text-xs" data-bs-toggle="tooltip" title="Dipakai untuk menghitung dasar potongan per jam atau per menit"></i>
        </label>
        <input type="number" name="working_hours_per_day" id="working_hours_per_day"
            class="form-control @error('working_hours_per_day') is-invalid @enderror"
            value="{{ $workingHours }}" min="1" required data-testid="deduction-rule-hours-input"
            @if($disableAutofill) autocomplete="off" spellcheck="false" @endif>
        @error('working_hours_per_day')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mt-3">
        <label class="form-label" for="working_days_per_month">Hari Kerja per Bulan</label>
        <input type="number" name="working_days_per_month" id="working_days_per_month"
            class="form-control @error('working_days_per_month') is-invalid @enderror"
            value="{{ $workingDays }}" min="1" required data-testid="deduction-rule-days-input"
            @if($disableAutofill) autocomplete="off" spellcheck="false" @endif>
        @error('working_days_per_month')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mt-3">
        <label class="form-label" for="tolerance_minutes">Toleransi Telat (menit)</label>
        <input type="number" name="tolerance_minutes" id="tolerance_minutes"
            class="form-control @error('tolerance_minutes') is-invalid @enderror"
            value="{{ $toleranceMinutes }}" min="0" required data-testid="deduction-rule-tolerance-input"
            @if($disableAutofill) autocomplete="off" spellcheck="false" @endif>
        @error('tolerance_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mt-3">
        <label class="form-label" for="rate_type">Tipe Rate Potongan</label>
        <select name="rate_type" id="rate_type" class="form-control @error('rate_type') is-invalid @enderror" required data-testid="deduction-rule-rate-type-select" @if($disableAutofill) autocomplete="off" @endif>
            @foreach ($rateTypes as $rateTypeValue => $rateTypeLabel)
                <option value="{{ $rateTypeValue }}" @selected($selectedRateType === $rateTypeValue)>{{ $rateTypeLabel }}</option>
            @endforeach
        </select>
        @error('rate_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="text-muted">Proporsional menghitung berdasar proporsi gaji, flat memakai nominal tetap per menit.</small>
    </div>

    <div class="col-md-6 mt-3">
        <label class="form-label">Ringkasan Pengaturan</label>
        <div class="d-flex gap-2 flex-wrap mb-2">
            <span class="badge bg-gradient-warning text-dark">Jam Kerja</span>
            <span class="badge bg-gradient-info">Rate</span>
            <span class="badge bg-gradient-success">Alpha</span>
        </div>
        <p class="text-sm text-secondary mb-0">Gunakan kombinasi jam kerja, hari kerja, dan tipe rate yang sesuai dengan kebijakan payroll tenant Anda.</p>
    </div>

    <div class="col-12 mt-4">
        <div class="form-check form-switch">
            <input type="checkbox" name="alpha_full_day" id="alpha_full_day"
                class="form-check-input" value="1" data-testid="deduction-rule-alpha-switch"
                {{ $alphaFullDay ? 'checked' : '' }}>
            <label class="form-check-label" for="alpha_full_day">
                Alpha potong penuh 1 hari
            </label>
        </div>
        <small class="text-muted d-block mt-1">Aktifkan bila karyawan alpha harus dikenakan potongan setara satu hari kerja penuh.</small>
    </div>
</div>