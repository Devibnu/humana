@php($forceBlankDefaults = $forceBlankDefaults ?? false)
@php($disableAutofill = $disableAutofill ?? false)

<div class="row">
    <div class="col-md-6">
        <label class="form-label">Tenant</label>
        <select name="tenant_id" class="form-control" required data-testid="position-tenant-select" @if($disableAutofill) autocomplete="off" @endif>
            <option value="">Pilih tenant</option>
            @foreach ($tenants as $tenant)
                <option value="{{ $tenant->id }}" @selected(old('tenant_id', $forceBlankDefaults ? '' : ($position->tenant_id ?? '')) == $tenant->id)>{{ $tenant->name }}</option>
            @endforeach
        </select>
        <small class="text-muted">Pilih tenant utama untuk menentukan daftar departemen yang tersedia.</small>
    </div>
    <div class="col-md-6">
        <label class="form-label d-flex align-items-center gap-1">
            Nama Posisi
            <i class="fas fa-info-circle text-secondary text-xs" data-bs-toggle="tooltip" title="Gunakan nama resmi sesuai struktur organisasi"></i>
        </label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $forceBlankDefaults ? '' : ($position->name ?? '')) }}" required @if($disableAutofill) autocomplete="new-password" autocapitalize="off" spellcheck="false" @endif>
    </div>
    <div class="col-md-6 mt-3">
        <label class="form-label d-flex align-items-center gap-1">
            Kode Posisi
            <i class="fas fa-info-circle text-secondary text-xs" data-bs-toggle="tooltip" title="Opsional, untuk kode internal"></i>
        </label>
        <input type="text" name="code" class="form-control" value="{{ old('code', $forceBlankDefaults ? '' : ($position->code ?? '')) }}" placeholder="Contoh: MGR-01" @if($disableAutofill) autocomplete="new-password" autocapitalize="off" spellcheck="false" @endif>
    </div>
    <div class="col-md-6 mt-3">
        <label class="form-label">Departemen</label>
        <select name="department_id" class="form-control" required data-testid="position-department-select" @if($disableAutofill) autocomplete="off" @endif>
            <option value="">Pilih departemen</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}"
                        data-tenant-id="{{ $department->tenant_id }}"
                        @selected((string) old('department_id', $forceBlankDefaults ? '' : ($position->department_id ?? '')) === (string) $department->id)>
                    {{ $department->name }} ({{ $department->tenant?->name ?? 'Tenant' }})
                </option>
            @endforeach
        </select>
        <small class="text-muted">Hanya departemen yang sesuai tenant terpilih yang dapat digunakan.</small>
    </div>
    <div class="col-12 mt-3">
        <label class="form-label">Deskripsi Posisi</label>
        <textarea name="description" class="form-control" rows="4" placeholder="Tuliskan deskripsi singkat posisi" @if($disableAutofill) autocomplete="off" spellcheck="false" @endif>{{ old('description', $forceBlankDefaults ? '' : ($position->description ?? '')) }}</textarea>
    </div>
    <div class="col-md-6 mt-3">
        <label class="form-label">Status</label>
        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="badge bg-gradient-success">Aktif</span>
            <span class="badge bg-gradient-secondary">Non-Aktif</span>
        </div>
        <select name="status" class="form-control" required data-testid="position-status-select" @if($disableAutofill) autocomplete="off" @endif>
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $forceBlankDefaults ? 'active' : ($position->status ?? 'active')) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const tenantSelect = document.querySelector('[data-testid="position-tenant-select"]');
                const departmentSelect = document.querySelector('[data-testid="position-department-select"]');

                if (!tenantSelect || !departmentSelect) {
                    return;
                }

                const filterDepartments = () => {
                    const selectedTenantId = tenantSelect.value;

                    Array.from(departmentSelect.options).forEach((option, index) => {
                        if (index === 0) {
                            option.hidden = false;
                            return;
                        }

                        const matchesTenant = !selectedTenantId || option.dataset.tenantId === selectedTenantId;

                        option.hidden = !matchesTenant;
                        option.disabled = !matchesTenant;

                        if (!matchesTenant && option.selected) {
                            departmentSelect.value = '';
                        }
                    });
                };

                filterDepartments();
                tenantSelect.addEventListener('change', filterDepartments);
            });
        </script>
    @endpush
@endonce