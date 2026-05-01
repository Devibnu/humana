<div class="row">
    <div class="col-md-6">
        <label class="form-label">Nama Tenant <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $tenant->name ?? '') }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Kode <span class="text-danger">*</span></label>
        <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $tenant->code ?? '') }}" placeholder="Contoh: HMD-JKT" required>
        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6 mt-3">
        <label class="form-label">Domain <span class="text-danger">*</span></label>
        @if (!empty($tenant?->domain))
            <input type="hidden" name="domain" value="{{ old('domain', $tenant->domain) }}">
            <input type="text" class="form-control" value="{{ old('domain', $tenant->domain) }}" readonly>
            <small class="text-muted">Domain tenant dipertahankan saat edit untuk menghindari putusnya akses tenant yang sudah aktif.</small>
        @else
            <input type="text" name="domain" class="form-control @error('domain') is-invalid @enderror" value="{{ old('domain', $tenant->domain ?? '') }}" placeholder="contoh-tenant.test" required>
            @error('domain')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @endif
    </div>
    <div class="col-md-6 mt-3">
        <label class="form-label">Kontak</label>
        <input type="text" name="contact" class="form-control @error('contact') is-invalid @enderror" value="{{ old('contact', $tenant->contact ?? '') }}" placeholder="Nomor telepon atau email PIC">
        @error('contact')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6 mt-3">
        <label class="form-label">Logo / Favicon Tenant</label>
        <input type="file" name="branding" id="branding" class="form-control @error('branding') is-invalid @enderror" accept="image/*,.ico" data-testid="tenant-branding-input">
        @error('branding')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="text-muted">Satu file branding akan dipakai untuk logo tenant dan favicon. Gunakan PNG, JPG, WEBP, SVG, atau ICO. Maksimal 2 MB.</small>
        @php($activeBrandingPath = $tenant->branding_path)
        @if(!empty($activeBrandingPath))
            <div class="mt-2 d-flex align-items-center gap-3">
                <img src="{{ asset($activeBrandingPath) }}" alt="Logo {{ $tenant->name ?? 'tenant' }}" height="60" class="border rounded bg-white p-1">
                <img src="{{ asset($activeBrandingPath) }}" alt="Favicon {{ $tenant->name ?? 'tenant' }}" height="32" class="border rounded bg-white p-1">
            </div>
        @endif
    </div>
    <div class="col-md-6 mt-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-control" required>
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $tenant->status ?? 'active') === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-12 mt-3">
        <label class="form-label">Alamat</label>
        <textarea name="address" rows="3" class="form-control @error('address') is-invalid @enderror" placeholder="Alamat lengkap tenant">{{ old('address', $tenant->address ?? '') }}</textarea>
        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12 mt-3">
        <label class="form-label">Teks Footer Login</label>
        <textarea name="login_footer_text" rows="2" class="form-control @error('login_footer_text') is-invalid @enderror" placeholder="Contoh: Copyright (c) {year} Jasa ibnu. All rights reserved.">{{ old('login_footer_text', $tenant->login_footer_text ?? '') }}</textarea>
        @error('login_footer_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="text-muted">Teks ini tampil di footer halaman login. Gunakan <strong>{year}</strong> untuk tahun berjalan otomatis.</small>
    </div>
</div>