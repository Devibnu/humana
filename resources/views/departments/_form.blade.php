<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label d-flex align-items-center gap-1">
            Nama Departemen <span class="text-danger">*</span>
            <i class="fas fa-info-circle text-secondary text-xs" data-bs-toggle="tooltip" title="Nama departemen harus unik dalam satu tenant."></i>
        </label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $department->name ?? '') }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label d-flex align-items-center gap-1">
            Kode Departemen
            <i class="fas fa-info-circle text-secondary text-xs" data-bs-toggle="tooltip" title="Kode singkat internal untuk identifikasi departemen."></i>
        </label>
        <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $department->code ?? '') }}" placeholder="Contoh: HRD">
        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label d-flex align-items-center gap-1">
            Tenant <span class="text-danger">*</span>
            <i class="fas fa-info-circle text-secondary text-xs" data-bs-toggle="tooltip" title="Pilih tenant tempat departemen ini digunakan."></i>
        </label>
        <select name="tenant_id" class="form-control @error('tenant_id') is-invalid @enderror" required data-testid="department-tenant-select">
            <option value="">Pilih tenant</option>
            @foreach ($tenants as $tenant)
                <option value="{{ $tenant->id }}" @selected(old('tenant_id', $department->tenant_id ?? '') == $tenant->id)>{{ $tenant->name }}</option>
            @endforeach
        </select>
        @error('tenant_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label d-flex align-items-center gap-1">
            Status
            <i class="fas fa-info-circle text-secondary text-xs" data-bs-toggle="tooltip" title="Matikan jika departemen sudah tidak aktif digunakan."></i>
        </label>
        <div class="form-check form-switch mt-2">
            <input type="hidden" name="status" value="inactive">
            <input class="form-check-input" type="checkbox" role="switch" id="department_status" name="status" value="active"
                @checked(old('status', $department->status ?? 'active') === 'active')>
            <label class="form-check-label" for="department_status">Departemen aktif</label>
        </div>
        @error('status')<div class="text-danger text-xs mt-1">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <label class="form-label">Deskripsi</label>
        <textarea name="description" rows="4" class="form-control @error('description') is-invalid @enderror" placeholder="Tulis deskripsi singkat fungsi departemen">{{ old('description', $department->description ?? '') }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>