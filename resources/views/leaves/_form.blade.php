@php
    $localizedStatuses = [
        'pending' => 'Pending',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
    ];
    $approvalStageLabels = [
        'supervisor' => 'Supervisor',
        'manager' => 'Manager',
        'hr' => 'HR',
    ];

    $selectedStatus = old('status', $leave->status ?? 'pending');
    $statusBadgeClass = $selectedStatus === 'approved'
        ? 'bg-gradient-success'
        : ($selectedStatus === 'rejected' ? 'bg-gradient-danger' : 'bg-gradient-warning text-dark');
    $statusDisplayLabel = $localizedStatuses[$selectedStatus] ?? ucfirst($selectedStatus);

    if ($selectedStatus === 'pending' && ($leave->current_approval_role ?? null)) {
        $statusDisplayLabel .= ' (Menunggu ' . ($approvalStageLabels[$leave->current_approval_role] ?? ucfirst($leave->current_approval_role)) . ')';
    }
@endphp

<div class="row">
    <div class="col-md-6">
        <label class="form-label">Tenant <span class="text-danger">*</span></label>
        @if ($isTenantLocked)
            <input type="hidden" name="tenant_id" value="{{ $scopedTenantId }}">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge bg-gradient-warning" data-testid="leave-tenant-locked-badge">Tenant Terkunci</span>
                <small class="text-muted">Tenant mengikuti scope akun Anda.</small>
            </div>
            <input type="text" class="form-control" value="{{ $tenants->first()?->name ?? 'Tenant tidak ditemukan' }}" readonly>
        @else
            <select name="tenant_id" class="form-select" required data-testid="leave-tenant-select" {{ $canEditDetails ? '' : 'disabled' }}>
                <option value="">Pilih tenant</option>
                @foreach ($tenants as $tenant)
                    <option value="{{ $tenant->id }}" @selected((string) old('tenant_id', $selectedTenantId ?? $leave->tenant_id ?? '') === (string) $tenant->id)>
                        {{ $tenant->name }}
                    </option>
                @endforeach
            </select>
        @endif
    </div>
    <div class="col-md-6">
        <label class="form-label">Karyawan <span class="text-danger">*</span></label>
        @if ($isEmployeeLocked)
            <input type="hidden" name="employee_id" value="{{ $employees->first()?->id }}">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge bg-gradient-info" data-testid="leave-employee-locked-badge">Permintaan Pribadi</span>
                <small class="text-muted">Anda hanya dapat mengajukan cuti untuk diri sendiri.</small>
            </div>
            <input type="text" class="form-control" value="{{ $employees->first()?->name ?? 'Karyawan tidak ditemukan' }}" readonly>
        @else
            <select name="employee_id" class="form-select" required data-testid="leave-employee-select" {{ $canEditDetails ? '' : 'disabled' }}>
                <option value="">Pilih karyawan</option>
                @foreach ($allEmployees as $employee)
                    <option value="{{ $employee->id }}" data-tenant-id="{{ $employee->tenant_id }}" @selected((string) old('employee_id', $leave->employee_id ?? '') === (string) $employee->id)>
                        {{ $employee->name }}
                    </option>
                @endforeach
            </select>
            <small class="text-muted">Daftar karyawan mengikuti tenant yang dipilih.</small>
        @endif
    </div>
    <div class="col-md-6 mt-3">
        <label class="form-label d-flex align-items-center gap-2">
            <span>Jenis Cuti <span class="text-danger">*</span></span>
            <span class="text-secondary" data-bs-toggle="tooltip" title="Pilih kategori cuti yang paling sesuai agar proses review lebih akurat.">
                <i class="fas fa-circle-info"></i>
            </span>
        </label>
        <select name="leave_type_id" class="form-select" required data-testid="leave-type-select" {{ $canEditDetails ? '' : 'disabled' }}>
            <option value="">Pilih jenis cuti</option>
            @foreach ($tenantLeaveTypes as $type)
                <option
                    value="{{ $type->id }}"
                    data-wajib-lampiran="{{ $type->wajib_lampiran ? '1' : '0' }}"
                    data-alur-persetujuan="{{ $type->alur_persetujuan ?? 'single' }}"
                    data-wajib-persetujuan="{{ $type->wajib_persetujuan ? '1' : '0' }}"
                    @selected((string) old('leave_type_id', $leave->leave_type_id ?? '') === (string) $type->id)
                >{{ $type->name }}</option>
            @endforeach
        </select>
        <small class="text-muted" data-testid="leave-type-config-hint">Konfigurasi lampiran dan approval mengikuti master jenis cuti.</small>
    </div>
    <div class="col-md-6 mt-3">
        <label class="form-label d-flex align-items-center gap-2">
            <span>Status</span>
            <span class="text-secondary" data-bs-toggle="tooltip" title="Status awal permintaan cuti adalah Pending dan akan ditinjau sesuai scope tenant.">
                <i class="fas fa-circle-info"></i>
            </span>
        </label>
        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="badge {{ $statusBadgeClass }}" data-testid="leave-status-badge">{{ $statusDisplayLabel }}</span>
            <small class="text-muted">Manager dapat meninjau permintaan cuti tenant scoped</small>
        </div>
        @if ($leave->exists && $canChangeStatus)
            <select name="status" class="form-select" required data-testid="leave-status-select">
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $leave->status ?? 'pending') === $value)>{{ $localizedStatuses[$value] ?? $label }}</option>
                @endforeach
            </select>
        @else
            <input type="hidden" name="status" value="pending">
            <select class="form-select" disabled data-testid="leave-status-select">
                <option selected>Pending</option>
                <option>Disetujui</option>
                <option>Ditolak</option>
            </select>
        @endif
    </div>
    <div class="col-12 mt-3">
        <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
        <input type="date" name="start_date" class="form-control" value="{{ old('start_date', optional($leave->start_date)->format('Y-m-d')) }}" required data-testid="leave-start-date" {{ $canEditDetails ? '' : 'readonly' }}>
    </div>
    <div class="col-12 mt-3">
        <label class="form-label">Tanggal Selesai <span class="text-danger">*</span></label>
        <input type="date" name="end_date" class="form-control" value="{{ old('end_date', optional($leave->end_date)->format('Y-m-d')) }}" required data-testid="leave-end-date" {{ $canEditDetails ? '' : 'readonly' }}>
    </div>
    <div class="col-12 mt-3">
        <label class="form-label">Alasan <span class="text-danger">*</span></label>
        <textarea name="reason" rows="4" class="form-control" placeholder="Tuliskan alasan cuti" required data-testid="leave-reason" {{ $canEditDetails ? '' : 'readonly' }}>{{ old('reason', $leave->reason ?? '') }}</textarea>
    </div>
    <div class="col-12 mt-3">
        <label class="form-label">Lampiran Bukti</label>
        <input type="file" name="attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png" data-testid="leave-attachment" {{ $canEditDetails ? '' : 'disabled' }}>
        <small class="text-muted" data-testid="leave-attachment-hint">Format: PDF/JPG/PNG (max 2MB). Jika jenis cuti mensyaratkan lampiran, field ini wajib.</small>
        @if (!empty($leave->attachment_path))
            <div class="mt-2">
                <a href="{{ asset('storage/' . $leave->attachment_path) }}" target="_blank" rel="noopener" class="text-sm">Lihat lampiran tersimpan</a>
            </div>
        @endif
    </div>
</div>

@once
    @push('dashboard')
        <script>
            window.addEventListener('load', function () {
                var tenantSelect = document.querySelector('[data-testid="leave-tenant-select"]');
                var employeeSelect = document.querySelector('[data-testid="leave-employee-select"]');

                if (!tenantSelect || !employeeSelect) {
                    return;
                }

                var placeholderOption = employeeSelect.querySelector('option[value=""]');
                var employeeOptions = Array.from(employeeSelect.querySelectorAll('option[data-tenant-id]'));

                function filterEmployeeOptions() {
                    var selectedTenantId = tenantSelect.value;
                    var currentValue = employeeSelect.value;
                    var matchedCurrentValue = false;

                    employeeOptions.forEach(function (option) {
                        var shouldShow = !selectedTenantId || option.dataset.tenantId === selectedTenantId;

                        option.hidden = !shouldShow;
                        option.disabled = !shouldShow;

                        if (shouldShow && option.value === currentValue) {
                            matchedCurrentValue = true;
                        }
                    });

                    if (!matchedCurrentValue) {
                        employeeSelect.value = placeholderOption ? placeholderOption.value : '';
                    }
                }

                filterEmployeeOptions();
                tenantSelect.addEventListener('change', filterEmployeeOptions);
            });
        </script>
    @endpush
@endonce