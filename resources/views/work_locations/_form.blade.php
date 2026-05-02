<div class="row g-3">
    <div class="col-lg-6">
        <label class="form-label">Tenant <span class="text-danger">*</span></label>
        @if ($isTenantLocked)
            <input type="hidden" name="tenant_id" value="{{ $scopedTenantId }}">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge bg-gradient-warning" data-testid="tenant-locked-badge">Tenant Terkunci</span>
                <small class="text-muted">Manager hanya dapat menggunakan tenant miliknya sendiri.</small>
            </div>
            <input type="text" class="form-control" value="{{ $tenants->first()?->name ?? 'Tenant tidak ditemukan' }}" readonly>
        @else
            <select name="tenant_id" class="form-control @error('tenant_id') is-invalid @enderror" required>
                <option value="">Pilih tenant</option>
                @foreach ($tenants as $tenant)
                    <option value="{{ $tenant->id }}" @selected(old('tenant_id', $workLocation->tenant_id ?? '') == $tenant->id)>{{ $tenant->name }}</option>
                @endforeach
            </select>
            @error('tenant_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @endif
    </div>
    <div class="col-lg-6">
        <label class="form-label d-flex align-items-center gap-1">
            Nama Lokasi Kerja <span class="text-danger">*</span>
            <i class="fas fa-info-circle text-secondary text-xs" data-bs-toggle="tooltip" title="Gunakan nama resmi lokasi kerja"></i>
        </label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $workLocation->name ?? '') }}" placeholder="Contoh: Kantor Pusat Jakarta" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-lg-4">
        <label class="form-label d-flex align-items-center gap-1">
            Radius Absensi <span class="text-danger">*</span>
            <i class="fas fa-info-circle text-secondary text-xs" data-bs-toggle="tooltip" title="Radius validasi kehadiran"></i>
        </label>
        <div class="input-group">
            <input type="number" min="1" max="100000" name="radius" class="form-control @error('radius') is-invalid @enderror" value="{{ old('radius', $workLocation->radius ?? '') }}" placeholder="Contoh: 150" required>
            <span class="input-group-text">meter</span>
            @error('radius')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-lg-4">
        <label class="form-label">Latitude <span class="text-danger">*</span></label>
        <input type="number" step="0.0000001" name="latitude" class="form-control @error('latitude') is-invalid @enderror" value="{{ old('latitude', $workLocation->latitude ?? '') }}" placeholder="Contoh: -6.1754" required>
        @error('latitude')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-lg-4">
        <label class="form-label">Longitude <span class="text-danger">*</span></label>
        <input type="number" step="0.0000001" name="longitude" class="form-control @error('longitude') is-invalid @enderror" value="{{ old('longitude', $workLocation->longitude ?? '') }}" placeholder="Contoh: 106.8272" required>
        @error('longitude')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <label class="form-label">Alamat</label>
        <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="4" placeholder="Tulis alamat lengkap lokasi kerja">{{ old('address', $workLocation->address ?? '') }}</textarea>
        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
