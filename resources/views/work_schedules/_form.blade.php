@php($currentUser = auth()->user())
@php($isTenantLocked = (bool) $currentUser?->isManager())

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Tenant <span class="text-danger">*</span></label>
        @if ($isTenantLocked)
            <input type="hidden" name="tenant_id" value="{{ $currentUser->tenant_id }}">
            <input type="text" class="form-control" value="{{ $currentUser->tenant?->name ?? 'Tenant Anda' }}" readonly>
        @else
            <select name="tenant_id" class="form-control @error('tenant_id') is-invalid @enderror" required data-testid="work-schedule-tenant-select">
                <option value="">Pilih tenant</option>
                @foreach ($tenants as $tenant)
                    <option value="{{ $tenant->id }}" @selected((string) old('tenant_id', $workSchedule->tenant_id ?? '') === (string) $tenant->id)>
                        {{ $tenant->name }}
                    </option>
                @endforeach
            </select>
            @error('tenant_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @endif
    </div>
    <div class="col-md-6">
        <label class="form-label">Nama Jadwal <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $workSchedule->name ?? '') }}" placeholder="Contoh: Shift Pagi" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Kode</label>
        <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $workSchedule->code ?? '') }}" placeholder="Otomatis dari nama jika kosong">
        <small class="text-muted">Kode internal jadwal kerja.</small>
        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Jam Masuk <span class="text-danger">*</span></label>
        <input type="time" name="check_in_time" class="form-control @error('check_in_time') is-invalid @enderror" value="{{ old('check_in_time', substr($workSchedule->check_in_time ?? '08:00', 0, 5)) }}" required>
        @error('check_in_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Jam Pulang <span class="text-danger">*</span></label>
        <input type="time" name="check_out_time" class="form-control @error('check_out_time') is-invalid @enderror" value="{{ old('check_out_time', substr($workSchedule->check_out_time ?? '17:00', 0, 5)) }}" required>
        @error('check_out_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Toleransi Telat (menit)</label>
        <input type="number" name="late_tolerance_minutes" min="0" max="240" class="form-control @error('late_tolerance_minutes') is-invalid @enderror" value="{{ old('late_tolerance_minutes', $workSchedule->late_tolerance_minutes ?? 0) }}" required>
        @error('late_tolerance_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Toleransi Pulang Cepat (menit)</label>
        <input type="number" name="early_leave_tolerance_minutes" min="0" max="240" class="form-control @error('early_leave_tolerance_minutes') is-invalid @enderror" value="{{ old('early_leave_tolerance_minutes', $workSchedule->early_leave_tolerance_minutes ?? 0) }}" required>
        @error('early_leave_tolerance_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-2">
        <label class="form-label">Urutan</label>
        <input type="number" name="sort_order" min="0" max="9999" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', $workSchedule->sort_order ?? 0) }}">
        @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-2">
        <label class="form-label">Status <span class="text-danger">*</span></label>
        <select name="status" class="form-control @error('status') is-invalid @enderror" required>
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $workSchedule->status ?? 'active') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <label class="form-label">Deskripsi</label>
        <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror" placeholder="Catatan opsional tentang jadwal ini">{{ old('description', $workSchedule->description ?? '') }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
