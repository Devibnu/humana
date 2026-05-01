<div class="row">
    <div class="col-md-6">
        <label class="form-label">Tenant</label>
        @if ($isTenantLocked)
            <input type="hidden" name="tenant_id" value="{{ $scopedTenantId }}">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge bg-gradient-warning" data-testid="tenant-locked-badge">Tenant Terkunci</span>
                <small class="text-muted">Manager hanya dapat menggunakan tenant miliknya sendiri.</small>
            </div>
            <input type="text" class="form-control" value="{{ $tenants->first()?->name ?? 'Tenant tidak ditemukan' }}" readonly>
        @else
            <select name="tenant_id" class="form-control" required>
                <option value="">Pilih tenant</option>
                @foreach ($tenants as $tenant)
                    <option value="{{ $tenant->id }}" @selected(old('tenant_id', $workLocation->tenant_id ?? '') == $tenant->id)>{{ $tenant->name }}</option>
                @endforeach
            </select>
        @endif
    </div>
    <div class="col-md-6">
        <label class="form-label d-flex align-items-center gap-1">
            Nama Lokasi Kerja
            <i class="fas fa-info-circle text-secondary text-xs" data-bs-toggle="tooltip" title="Gunakan nama resmi lokasi kerja"></i>
        </label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $workLocation->name ?? '') }}" placeholder="Contoh: Kantor Pusat Jakarta" required>
    </div>

    <div class="col-md-6 mt-3">
        <label class="form-label d-flex align-items-center gap-1">
            Radius (meter)
            <i class="fas fa-info-circle text-secondary text-xs" data-bs-toggle="tooltip" title="Radius validasi kehadiran"></i>
        </label>
        <input type="number" min="1" max="100000" name="radius" class="form-control" value="{{ old('radius', $workLocation->radius ?? '') }}" placeholder="Contoh: 150" required>
    </div>
    <div class="col-md-6 mt-3"></div>

    <div class="col-12 mt-3">
        <label class="form-label">Alamat</label>
        <textarea name="address" class="form-control" rows="4" placeholder="Tulis alamat lengkap lokasi kerja">{{ old('address', $workLocation->address ?? '') }}</textarea>
    </div>

    <div class="col-md-6 mt-3">
        <label class="form-label">Latitude</label>
        <input type="number" step="0.0000001" name="latitude" class="form-control" value="{{ old('latitude', $workLocation->latitude ?? '') }}" placeholder="Contoh: -6.1754" required>
    </div>
    <div class="col-md-6 mt-3">
        <label class="form-label">Longitude</label>
        <input type="number" step="0.0000001" name="longitude" class="form-control" value="{{ old('longitude', $workLocation->longitude ?? '') }}" placeholder="Contoh: 106.8272" required>
    </div>
</div>
