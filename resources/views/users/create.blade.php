@extends('layouts.user_type.auth')

@section('content')

<div class="row">
    <div class="col-12">

        <x-flash-messages />

        <div class="card mx-4 mb-4">

            {{-- Card Header --}}
            <div class="card-header pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0">Create New User</h5>
                    <p class="text-sm text-secondary mb-0">Tambahkan akun user baru ke sistem HRIS Humana.</p>
                </div>
                <a href="{{ route('users.index') }}" class="btn btn-light btn-sm mb-0">
                    <i class="fas fa-arrow-left me-1"></i> Back to Users
                </a>
            </div>

            <div class="card-body">
                <form action="{{ route('users.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="row g-4">

                        {{-- ── Account Information ── --}}
                        <div class="col-lg-7">
                            <div class="card border shadow-xs h-100">
                                <div class="card-header pb-0">
                                    <h6 class="mb-0">Account Information</h6>
                                    <p class="text-xs text-secondary mb-0">Nama, email, dan password akun.</p>
                                </div>
                                <div class="card-body pt-3">

                                    <div class="mb-3">
                                        <label class="form-label">
                                            Pilih Karyawan <span class="text-danger">*</span>
                                        </label>
                                        <select
                                            name="employee_id"
                                            id="employee_id"
                                            class="form-control @error('employee_id') is-invalid @enderror"
                                            required
                                        >
                                            <option value="">Pilih karyawan</option>
                                            @foreach ($linkableEmployees as $employee)
                                                <option
                                                    value="{{ $employee->id }}"
                                                    data-name="{{ $employee->name }}"
                                                    data-email="{{ $employee->email }}"
                                                    @selected((string) old('employee_id') === (string) $employee->id)
                                                >
                                                    {{ $employee->name }} ({{ $employee->email }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('employee_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        @if ($linkableEmployees->isEmpty())
                                            <small class="text-warning">Tidak ada karyawan yang tersedia untuk dibuatkan akun.</small>
                                        @endif
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">
                                            Full Name <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            id="name"
                                            name="name"
                                            class="form-control @error('name') is-invalid @enderror"
                                            value="{{ old('name') }}"
                                            placeholder="e.g. Budi Santoso"
                                            required
                                            readonly
                                            data-bs-toggle="tooltip"
                                            title="Nama lengkap user"
                                        >
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">
                                            Email Address <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            type="email"
                                            id="email"
                                            name="email"
                                            class="form-control @error('email') is-invalid @enderror"
                                            value="{{ old('email') }}"
                                            placeholder="user@humana.id"
                                            required
                                            readonly
                                            data-bs-toggle="tooltip"
                                            title="Email digunakan untuk login"
                                        >
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">
                                                Password <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <input
                                                    type="password"
                                                    id="password"
                                                    name="password"
                                                    class="form-control @error('password') is-invalid @enderror"
                                                    placeholder="Min. 8 karakter"
                                                    required
                                                    autocomplete="new-password"
                                                >
                                                <button
                                                    class="btn btn-outline-secondary mb-0 password-toggle"
                                                    type="button"
                                                    data-target="password"
                                                    aria-label="Lihat password"
                                                    title="Lihat password"
                                                >
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                            @error('password')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">
                                                Confirm Password <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <input
                                                    type="password"
                                                    id="password_confirmation"
                                                    name="password_confirmation"
                                                    class="form-control"
                                                    placeholder="Ulangi password"
                                                    required
                                                    autocomplete="new-password"
                                                >
                                                <button
                                                    class="btn btn-outline-secondary mb-0 password-toggle"
                                                    type="button"
                                                    data-target="password_confirmation"
                                                    aria-label="Lihat confirm password"
                                                    title="Lihat confirm password"
                                                >
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Avatar Upload --}}
                                    <div class="mb-0">
                                        <label class="form-label">
                                            Avatar
                                            <small class="text-muted">(Opsional — jpg/png maks. 2 MB)</small>
                                        </label>
                                        <div class="d-flex align-items-center gap-3 flex-wrap">
                                            <div id="avatar-preview-wrap" class="avatar avatar-lg d-none">
                                                <img id="avatar-preview" src="#" alt="Preview" class="w-100 border-radius-lg shadow-sm">
                                            </div>
                                            <input
                                                type="file"
                                                name="avatar"
                                                id="avatar-input"
                                                class="form-control @error('avatar') is-invalid @enderror"
                                                accept="image/jpeg,image/png,image/gif"
                                                onchange="previewAvatar(event)"
                                            >
                                            @error('avatar')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        {{-- ── Role & Tenant ── --}}
                        <div class="col-lg-5">
                            <div class="card border shadow-xs h-100">
                                <div class="card-header pb-0">
                                    <h6 class="mb-0">Role & Tenant</h6>
                                    <p class="text-xs text-secondary mb-0">Atur akses dan organisasi user.</p>
                                </div>
                                <div class="card-body pt-3">

                                    {{-- Tenant --}}
                                    <div class="mb-3">
                                        <label class="form-label">
                                            Tenant <span class="text-danger">*</span>
                                        </label>
                                        @if ($isTenantLocked)
                                            <input type="hidden" name="tenant_id" value="{{ $scopedTenantId }}">
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <span class="badge bg-gradient-warning">Tenant Locked</span>
                                                <small class="text-muted">Dibatasi ke tenant Anda.</small>
                                            </div>
                                            <input type="text" class="form-control" value="{{ $tenants->first()?->name ?? '-' }}" readonly>
                                        @else
                                            <select name="tenant_id" class="form-control @error('tenant_id') is-invalid @enderror" required data-testid="tenant-select">
                                                <option value="">— Pilih Tenant —</option>
                                                @foreach ($tenants as $tenant)
                                                    <option value="{{ $tenant->id }}" @selected(old('tenant_id') == $tenant->id)>
                                                        {{ $tenant->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('tenant_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        @endif
                                    </div>

                                    {{-- Role --}}
                                    <div class="mb-3">
                                        <label class="form-label">
                                            Pilih Peran <span class="text-danger">*</span>
                                        </label>
                                        <select name="role_id" class="form-control @error('role_id') is-invalid @enderror" required>
                                            <option value="">— Pilih Peran —</option>
                                            @foreach ($assignableRoles as $role)
                                                <option value="{{ $role->id }}" @selected((string) old('role_id') === (string) $role->id || (old('role_id') === null && $role->system_key === 'employee'))>
                                                    {{ $role->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('role_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    {{-- Status Toggle --}}
                                    <div class="mb-0">
                                        <label class="form-label d-block">Status</label>
                                        <div class="d-flex gap-3">
                                            @foreach ($statuses as $value => $label)
                                                <div class="form-check">
                                                    <input
                                                        class="form-check-input"
                                                        type="radio"
                                                        name="status"
                                                        id="status_{{ $value }}"
                                                        value="{{ $value }}"
                                                        @checked(old('status', 'active') == $value)
                                                    >
                                                    <label class="form-check-label" for="status_{{ $value }}">
                                                        <span class="badge badge-sm {{ $value === 'active' ? 'bg-gradient-success' : 'bg-gradient-secondary' }}">
                                                            {{ $label }}
                                                        </span>
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>

                    {{-- Action Buttons --}}
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('users.index') }}" class="btn btn-light mb-0">Cancel</a>
                        <button type="submit" class="btn bg-gradient-primary mb-0">
                            <i class="fas fa-save me-1"></i> Save User
                        </button>
                    </div>

                </form>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
function previewAvatar(event) {
    const file = event.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function (e) {
        document.getElementById('avatar-preview').src = e.target.result;
        document.getElementById('avatar-preview-wrap').classList.remove('d-none');
    };
    reader.readAsDataURL(file);
}

(function () {
    const employeeSelect = document.getElementById('employee_id');
    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');

    if (!employeeSelect || !nameInput || !emailInput) {
        return;
    }

    const syncEmployeeFields = () => {
        const selectedOption = employeeSelect.options[employeeSelect.selectedIndex];
        nameInput.value = selectedOption?.dataset.name || '';
        emailInput.value = selectedOption?.dataset.email || '';
    };

    employeeSelect.addEventListener('change', syncEmployeeFields);
    syncEmployeeFields();
})();

(function () {
    document.querySelectorAll('.password-toggle').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.target);
            const icon = button.querySelector('i');

            if (!input || !icon) {
                return;
            }

            const shouldShow = input.type === 'password';
            input.type = shouldShow ? 'text' : 'password';
            icon.classList.toggle('fa-eye', !shouldShow);
            icon.classList.toggle('fa-eye-slash', shouldShow);
            button.setAttribute('aria-label', shouldShow ? 'Tutup password' : 'Lihat password');
            button.setAttribute('title', shouldShow ? 'Tutup password' : 'Lihat password');
        });
    });
})();
</script>
@endpush

@endsection
