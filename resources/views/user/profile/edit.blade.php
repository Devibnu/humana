@extends('layouts.user_type.auth')

@section('content')

<div class="row humana-mobile-shell">
    <div class="col-12">

        <x-flash-messages />

        <div class="card mx-4 mb-4 humana-mobile-card">

            <div class="card-header pb-0 d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <h5 class="mb-1">Edit Profile</h5>
                    <p class="text-sm mb-0">Update your Humana HRIS account details without changing employee master data.</p>
                </div>
                <a href="{{ route('profile') }}" class="btn btn-light btn-sm mb-0 humana-back-button">
                    <i class="fas fa-arrow-left me-1"></i> Back to Profile
                </a>
            </div>

            <div class="card-body">
                <form action="{{ route('user-profile.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="row g-4 humana-form-grid">

                        {{-- ── Account Information ── --}}
                        <div class="col-lg-6">
                            <div class="card border shadow-xs h-100 humana-form-section">
                                <div class="card-header pb-0">
                                    <h6 class="mb-0">Account Information</h6>
                                    <p class="text-xs text-secondary mb-0">Update login identity and avatar.</p>
                                </div>
                                <div class="card-body">

                                    {{-- Avatar preview row --}}
                                    <div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
                                        @if ($avatarUrl)
                                            <img src="{{ $avatarUrl }}" alt="{{ $user->name }} avatar"
                                                 class="avatar avatar-xxl border-radius-xl shadow-sm object-fit-cover">
                                        @else
                                            <div class="avatar avatar-xxl border-radius-xl bg-gradient-dark d-flex align-items-center justify-content-center shadow-sm">
                                                <span class="text-white text-xl font-weight-bold">{{ $avatarInitials }}</span>
                                            </div>
                                        @endif
                                        <div>
                                            <h6 class="mb-1">{{ $user->name }}</h6>
                                            <p class="text-sm text-secondary mb-1">{{ $user->email }}</p>
                                            <span class="badge {{ $roleBadgeClass }}">{{ ucfirst(str_replace('_', ' ', $user->role)) }}</span>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Name <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                               value="{{ old('name', $user->name) }}" required>
                                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
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
                                            <small class="text-muted">(Opsional — JPG/PNG/WEBP maks. 2 MB)</small>
                                        </label>
                                        <input type="file" name="avatar"
                                               class="form-control @error('avatar') is-invalid @enderror"
                                               accept=".jpg,.jpeg,.png,.webp">
                                        @error('avatar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    @if ($avatarUrl)
                                        <div class="form-check mt-3">
                                            <input class="form-check-input" type="checkbox" value="1"
                                                   name="remove_avatar" id="remove_avatar">
                                            <label class="form-check-label text-danger" for="remove_avatar">
                                                Hapus Avatar
                                            </label>
                                        </div>
                                    @endif

                                </div>
                            </div>
                        </div>

                        {{-- ── Employee Information ── --}}
                        <div class="col-lg-6">
                            <div class="card border shadow-xs h-100 humana-form-section">
                                <div class="card-header pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div>
                                        <h6 class="mb-0">Employee Information</h6>
                                        <p class="text-xs text-secondary mb-0">Data ini hanya bisa diubah via modul Employees.</p>
                                    </div>
                                    @if (! $employee && $user->isAdminHr())
                                        <a href="{{ route('employees.create', ['user_id' => $user->id, 'tenant_id' => $user->tenant_id]) }}"
                                           class="btn bg-gradient-primary btn-sm mb-0">
                                            Link Employee Record
                                        </a>
                                    @endif
                                </div>
                                <div class="card-body">
                                    @if ($employee)
                                        <div class="mb-3">
                                            <label class="form-label">NIK</label>
                                            <input type="text" class="form-control" value="{{ $employee->employee_code }}"
                                                   data-testid="employee-nik" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Department</label>
                                            <input type="text" class="form-control" value="{{ $employee->department?->name ?? '-' }}"
                                                   data-testid="employee-department" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Position</label>
                                            <input type="text" class="form-control" value="{{ $employee->position?->name ?? '-' }}"
                                                   data-testid="employee-position" readonly>
                                        </div>
                                        <div class="mb-0">
                                            <label class="form-label">Work Location</label>
                                            <input type="text" class="form-control" value="{{ $employee->workLocation?->name ?? '-' }}"
                                                   data-testid="employee-work-location" readonly>
                                        </div>
                                    @else
                                        <div class="border border-secondary border-radius-md p-3 bg-gray-100">
                                            <p class="text-sm font-weight-bold mb-1">Employee record not linked</p>
                                            <p class="text-sm text-secondary mb-0">This account can still update its login identity,
                                               but HRIS employee details remain read-only until an employee record is linked.</p>
                                        </div>
                                        @if (! $user->isAdminHr())
                                            <p class="text-sm text-secondary mt-3 mb-0">Only Admin HR can link this account to an employee record.</p>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>

                    </div>

                    {{-- Action Buttons --}}
                    <div class="d-flex justify-content-end gap-2 mt-4 humana-form-actions">
                        <a href="{{ route('profile') }}" class="btn btn-light mb-0">Cancel</a>
                        <button type="submit" class="btn bg-gradient-primary mb-0">
                            <i class="fas fa-save me-1"></i> Save Changes
                        </button>
                    </div>

                </form>
            </div>
        </div>

    </div>
</div>

@endsection
