@extends('layouts.user_type.auth')

@section('content')

<div class="row">
    <div class="col-12">

        <x-flash-messages />

        <div class="card mx-4 mb-4">

            <div class="card-header pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-1">Edit User</h5>
                    <p class="text-sm text-secondary mb-0">
                        Update data akun untuk <strong>{{ $user->name }}</strong>
                        <span class="badge bg-gradient-{{ match($user->roleKey()) { 'admin_hr' => 'danger', 'manager' => 'warning', default => 'secondary' } }} ms-1">
                            {{ $user->roleName() ?? ucfirst(str_replace('_', ' ', (string) $user->roleKey())) }}
                        </span>
                    </p>
                </div>
                <a href="{{ route('users.index') }}" class="btn btn-light btn-sm mb-0">
                    <i class="fas fa-arrow-left me-1"></i> Back to Users
                </a>
            </div>

            <div class="card-body">
                <form action="{{ route('users.update', $user) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="row g-4">

                        {{-- ── Account Information ── --}}
                        <div class="col-lg-7">
                            <div class="card border shadow-xs h-100">
                                <div class="card-header pb-0">
                                    <h6 class="mb-0">Account Information</h6>
                                    <p class="text-xs text-secondary mb-0">Login identity and avatar.</p>
                                </div>
                                <div class="card-body">

                                    {{-- Avatar preview --}}
                                    @if ($user->avatar_path)
                                        @php $avatarEditUrl = \Illuminate\Support\Facades\Storage::url($user->avatar_path); @endphp
                                        <div class="d-flex align-items-center gap-3 mb-4">
                                            <img src="{{ $avatarEditUrl }}" alt="{{ $user->name }}"
                                                 id="avatar-preview"
                                                 class="avatar avatar-xxl border-radius-xl shadow-sm object-fit-cover">
                                            <div>
                                                <h6 class="mb-1">{{ $user->name }}</h6>
                                                <p class="text-sm text-secondary mb-0">{{ $user->email }}</p>
                                            </div>
                                        </div>
                                    @else
                                        <div class="d-flex align-items-center gap-3 mb-4">
                                            <div class="avatar avatar-xxl border-radius-xl bg-gradient-dark d-flex align-items-center justify-content-center shadow-sm"
                                                 id="avatar-preview-placeholder">
                                                <span class="text-white text-xl font-weight-bold">
                                                    {{ strtoupper(substr($user->name, 0, 1)) }}{{ strtoupper(substr(strstr($user->name, ' ') ?: '', 1, 1)) }}
                                                </span>
                                            </div>
                                            <div>
                                                <h6 class="mb-1">{{ $user->name }}</h6>
                                                <p class="text-sm text-secondary mb-0">{{ $user->email }}</p>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="mb-3">
                                        <label class="form-label">Name <span class="text-danger">*</span></label>
                                        <input type="text" name="name"
                                               class="form-control @error('name') is-invalid @enderror"
                                               value="{{ old('name', $user->name) }}" required>
                                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" name="email"
                                               class="form-control @error('email') is-invalid @enderror"
                                               value="{{ old('email', $user->email) }}" required>
                                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">New Password</label>
                                            <input type="password" name="password"
                                                   class="form-control @error('password') is-invalid @enderror"
                                                   placeholder="Kosongkan jika tidak diganti"
                                                   autocomplete="new-password">
                                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Confirm New Password</label>
                                            <input type="password" name="password_confirmation"
                                                   class="form-control"
                                                   placeholder="Ulangi password baru"
                                                   autocomplete="new-password">
                                        </div>
                                    </div>

                                    <div class="mb-0">
                                        <label class="form-label">
                                            Avatar
                                            <small class="text-muted">(Opsional — JPG/PNG/GIF maks. 2 MB)</small>
                                        </label>
                                        <input type="file" name="avatar"
                                               class="form-control @error('avatar') is-invalid @enderror"
                                               accept=".jpg,.jpeg,.png,.gif"
                                               id="avatar-upload">
                                        @error('avatar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    @if ($user->avatar_path)
                                        <div class="form-check mt-3">
                                            <input class="form-check-input" type="checkbox"
                                                   value="1" name="remove_avatar" id="remove_avatar">
                                            <label class="form-check-label text-danger" for="remove_avatar">
                                                Hapus Avatar
                                            </label>
                                        </div>
                                    @endif

                                </div>
                            </div>
                        </div>

                        {{-- ── Role & Account Settings ── --}}
                        <div class="col-lg-5">
                            <div class="card border shadow-xs h-100">
                                <div class="card-header pb-0">
                                    <h6 class="mb-0">Role & Account</h6>
                                    <p class="text-xs text-secondary mb-0">Tenant, role, dan status akun.</p>
                                </div>
                                <div class="card-body">

                                    {{-- Tenant --}}
                                    <div class="mb-3">
                                        <label class="form-label">Tenant <span class="text-danger">*</span></label>
                                        @if ($isTenantLocked)
                                            <input type="hidden" name="tenant_id" value="{{ $scopedTenantId }}">
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <span class="badge bg-gradient-warning" data-testid="tenant-locked-badge">Tenant Locked</span>
                                                <small class="text-muted">Manager is restricted to their own tenant.</small>
                                            </div>
                                            <input type="text" class="form-control"
                                                   value="{{ $tenants->first()?->name ?? 'Tenant not found' }}" readonly>
                                        @else
                                            <select name="tenant_id"
                                                    class="form-control @error('tenant_id') is-invalid @enderror"
                                                    required data-testid="tenant-select">
                                                <option value="">Select tenant</option>
                                                @foreach ($tenants as $tenant)
                                                    <option value="{{ $tenant->id }}"
                                                            @selected(old('tenant_id', $user->tenant_id) == $tenant->id)>
                                                        {{ $tenant->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('tenant_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        @endif
                                    </div>

                                    {{-- Role --}}
                                    <div class="mb-3">
                                        <label class="form-label">Pilih Peran <span class="text-danger">*</span></label>
                                        <select name="role_id"
                                                class="form-control @error('role_id') is-invalid @enderror"
                                                required>
                                            <option value="">— Pilih Peran —</option>
                                            @foreach ($assignableRoles as $role)
                                                <option value="{{ $role->id }}"
                                                        @selected((string) old('role_id', $user->role_id) === (string) $role->id || ($user->role_id === null && old('role_id') === null && $role->system_key === $user->role))>
                                                    {{ $role->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('role_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    {{-- Status --}}
                                    <div class="mb-0">
                                        <label class="form-label">Status <span class="text-danger">*</span></label>
                                        <div class="d-flex flex-wrap gap-3 mt-1">
                                            @foreach ($statuses as $value => $label)
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio"
                                                           name="status" id="status_{{ $value }}" value="{{ $value }}"
                                                           @checked(old('status', $user->status) == $value)>
                                                    <label class="form-check-label" for="status_{{ $value }}">
                                                        <span class="badge bg-gradient-{{ $value === 'active' ? 'success' : 'secondary' }}">
                                                            {{ $label }}
                                                        </span>
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                        @error('status')<div class="text-danger text-xs mt-1">{{ $message }}</div>@enderror
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>

                    {{-- Action Buttons --}}
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('users.index') }}" class="btn btn-light mb-0">Cancel</a>
                        <button type="submit" class="btn bg-gradient-primary mb-0">
                            <i class="fas fa-save me-1"></i> Update User
                        </button>
                    </div>

                </form>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
    document.getElementById('avatar-upload')?.addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function (ev) {
            const preview = document.getElementById('avatar-preview');
            if (preview) preview.src = ev.target.result;
        };
        reader.readAsDataURL(file);
    });
</script>
@endpush

@endsection