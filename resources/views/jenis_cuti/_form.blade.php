@php
    $forceBlankDefaults = $forceBlankDefaults ?? false;
    $disableAutofill = $disableAutofill ?? false;
    $isTenantLocked = ! ($currentUser?->isAdminHr());
    $jenisCuti = $jenisCuti ?? $type;
    $selectedTenantValue = old('tenant_id', $forceBlankDefaults ? '' : ($selectedTenantId ?? $jenisCuti->tenant_id ?? ''));
    $selectedName = old('name', $forceBlankDefaults ? '' : ($jenisCuti->name ?? ''));
    $selectedFlow = old('alur_persetujuan', $forceBlankDefaults ? 'single' : ($jenisCuti->alur_persetujuan ?? 'single'));
    $isPaid = old('is_paid', $forceBlankDefaults ? true : ($jenisCuti->is_paid ?? true));
@endphp

<div class="row">
    @if ($isTenantLocked)
        <input type="hidden" name="tenant_id" value="{{ old('tenant_id', $selectedTenantValue ?: $selectedTenantId) }}">
    @else
        <div class="col-md-6">
            <label for="tenant_id" class="form-label">Tenant</label>
            <select name="tenant_id" id="tenant_id" class="form-control" required data-testid="leave-type-tenant-select" @if($disableAutofill) autocomplete="off" @endif>
                <option value="">Pilih tenant</option>
                @foreach ($tenants as $tenant)
                    <option value="{{ $tenant->id }}" @selected((string) $selectedTenantValue === (string) $tenant->id)>
                        {{ $tenant->name }}
                    </option>
                @endforeach
            </select>
            <small class="text-muted">Jenis cuti akan tersedia untuk tenant yang dipilih.</small>
        </div>
    @endif

    <input type="hidden" name="is_paid" value="{{ $isPaid ? 1 : 0 }}">

    <div class="{{ $isTenantLocked ? 'col-12' : 'col-md-6' }}">
        <label for="nama" class="form-label d-flex align-items-center gap-1">
            Nama Jenis Cuti
            <i class="fas fa-info-circle text-secondary text-xs" data-bs-toggle="tooltip" title="Gunakan nama kebijakan cuti yang jelas dan mudah dipahami"></i>
        </label>
        <input
            type="text"
            name="name"
            id="nama"
            class="form-control"
            value="{{ $selectedName }}"
            maxlength="100"
            placeholder="Contoh: Cuti Tahunan"
            required
            data-testid="leave-type-name-input"
            @if($disableAutofill) autocomplete="new-password" autocapitalize="off" spellcheck="false" @endif
        >
    </div>

    <div class="col-md-6 mt-3">
        <label for="alur_persetujuan" class="form-label">Alur Persetujuan</label>
        <select name="alur_persetujuan" id="alur_persetujuan" class="form-control" required data-testid="leave-type-flow-select">
            <option value="single" @selected($selectedFlow === 'single')>Single (Manager)</option>
            <option value="multi" @selected($selectedFlow === 'multi')>Multi (Supervisor → Manager → HR)</option>
            <option value="auto" @selected($selectedFlow === 'auto')>Auto (Langsung Disetujui)</option>
        </select>
        <small class="text-muted">Pilih jalur approval yang sesuai dengan aturan kebijakan cuti.</small>
    </div>

    <div class="col-md-6 mt-3">
        <label class="form-label">Ringkasan Pengaturan</label>
        <div class="d-flex gap-2 flex-wrap mb-2">
            <span class="badge bg-gradient-warning text-dark">Lampiran</span>
            <span class="badge bg-gradient-success">Persetujuan</span>
            <span class="badge bg-gradient-info">Workflow</span>
        </div>
        <p class="text-sm text-secondary mb-0">Atur kebutuhan dokumen dan approval agar proses pengajuan lebih konsisten di setiap tenant.</p>
    </div>

    <div class="col-md-6 mt-4">
        <div class="form-check form-switch">
            <input type="checkbox" name="wajib_lampiran" id="wajib_lampiran"
                   class="form-check-input" value="1"
                   {{ old('wajib_lampiran', $jenisCuti->wajib_lampiran ?? false) ? 'checked' : '' }}>
            <label class="form-check-label" for="wajib_lampiran">
                Wajib lampiran bukti
            </label>
        </div>
        <small class="text-muted d-block mt-1">Aktifkan jika pengajuan harus menyertakan dokumen pendukung.</small>
    </div>

    <div class="col-md-6 mt-4">
        <div class="form-check form-switch">
            <input type="checkbox" name="wajib_persetujuan" id="wajib_persetujuan"
                   class="form-check-input" value="1"
                   {{ old('wajib_persetujuan', $jenisCuti->wajib_persetujuan ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="wajib_persetujuan">
                Wajib persetujuan atasan
            </label>
        </div>
        <small class="text-muted d-block mt-1">Nonaktifkan hanya jika jenis cuti ini boleh langsung disetujui oleh sistem.</small>
    </div>
</div>
