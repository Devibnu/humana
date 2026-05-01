@php($currentUser = auth()->user())
@php($isTenantLocked = (bool) $currentUser?->isManager())

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Tenant <span class="text-danger">*</span></label>
        @if ($isTenantLocked)
            <input type="hidden" name="tenant_id" value="{{ $currentUser->tenant_id }}">
            <input type="text" class="form-control" value="{{ $currentUser->tenant?->name ?? 'Tenant Anda' }}" readonly>
        @else
            <select name="tenant_id" class="form-control @error('tenant_id') is-invalid @enderror" required data-testid="employee-level-tenant-select">
                <option value="">Pilih tenant</option>
                @foreach ($tenants as $tenant)
                    <option value="{{ $tenant->id }}" @selected((string) old('tenant_id', $employeeLevel->tenant_id ?? '') === (string) $tenant->id)>
                        {{ $tenant->name }}
                    </option>
                @endforeach
            </select>
            @error('tenant_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @endif
    </div>
    <div class="col-md-6">
        <label class="form-label">Nama Level <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $employeeLevel->name ?? '') }}" placeholder="Contoh: Staff Senior" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Kode</label>
        <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $employeeLevel->code ?? '') }}" placeholder="Otomatis dari nama jika kosong">
        <small class="text-muted">Kode ini yang disimpan ke data karyawan.</small>
        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Urutan</label>
        <input type="number" name="sort_order" min="0" max="9999" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', $employeeLevel->sort_order ?? 0) }}">
        @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Status <span class="text-danger">*</span></label>
        <select name="status" class="form-control @error('status') is-invalid @enderror" required>
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $employeeLevel->status ?? 'active') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <label class="form-label">Deskripsi</label>
        <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror" placeholder="Catatan opsional tentang level ini">{{ old('description', $employeeLevel->description ?? '') }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
