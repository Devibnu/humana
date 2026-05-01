<div class="row">
    <div class="col-md-6">
        <label class="form-label">Tenant</label>
        @if ($isTenantLocked)
            <input type="hidden" name="tenant_id" value="{{ $scopedTenantId }}">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge bg-gradient-warning" data-testid="tenant-locked-badge">Tenant Locked</span>
                <small class="text-muted">Manager is restricted to their own tenant.</small>
            </div>
            <input type="text" class="form-control" value="{{ $tenants->first()?->name ?? 'Tenant not found' }}" readonly>
        @else
            <select name="tenant_id" class="form-control" required data-testid="tenant-select">
                <option value="">Select tenant</option>
                @foreach ($tenants as $tenant)
                    <option value="{{ $tenant->id }}" @selected(old('tenant_id', $user->tenant_id ?? '') == $tenant->id)>
                        {{ $tenant->name }}
                    </option>
                @endforeach
            </select>
        @endif
    </div>
    <div class="col-md-6">
        <label class="form-label">Name</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $user->name ?? '') }}" required>
    </div>
    <div class="col-md-6 mt-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $user->email ?? '') }}" required>
    </div>
    <div class="col-md-6 mt-3">
        <label class="form-label">Password{{ isset($user) ? ' (leave blank to keep current password)' : '' }}</label>
        <input type="password" name="password" class="form-control" {{ isset($user) ? '' : 'required' }}>
    </div>
    <div class="col-md-6 mt-3">
        <label class="form-label">Role</label>
        <select name="role" class="form-control" required>
            @foreach ($roles as $value => $label)
                <option value="{{ $value }}" @selected(old('role', $user->role ?? 'employee') == $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6 mt-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-control" required>
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $user->status ?? 'active') == $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>
</div>