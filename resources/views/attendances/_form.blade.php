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
            <select name="tenant_id" class="form-control" required data-testid="attendance-tenant-select">
                <option value="">Pilih tenant</option>
                @foreach ($tenants as $tenant)
                    <option value="{{ $tenant->id }}" @selected(old('tenant_id', $attendance->tenant_id ?? '') == $tenant->id)>{{ $tenant->name }}</option>
                @endforeach
            </select>
        @endif
    </div>
    <div class="col-md-6">
        <label class="form-label">Karyawan</label>
        <select name="employee_id" class="form-control" required data-testid="attendance-employee-select">
            <option value="">Pilih karyawan</option>
            @foreach ($employees as $employee)
                <option
                    value="{{ $employee->id }}"
                    data-tenant-id="{{ $employee->tenant_id }}"
                    data-assigned-work-location-id="{{ $employee->work_location_id ?? '' }}"
                    data-work-location-name="{{ $employee->workLocation?->name ?? '' }}"
                    data-work-location-address="{{ $employee->workLocation?->address ?? '' }}"
                    data-work-location-radius="{{ $employee->workLocation?->radius ?? '' }}"
                    @selected(old('employee_id', $attendance->employee_id ?? '') == $employee->id)>{{ $employee->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6 mt-3">
        <label class="form-label">Assigned Work Location</label>
        <select name="work_location_id" class="form-control" required data-testid="attendance-work-location-select">
            <option value="">Pilih lokasi kerja</option>
            @foreach ($workLocations as $workLocation)
                <option value="{{ $workLocation->id }}"
                        data-tenant-id="{{ $workLocation->tenant_id }}"
                        data-address="{{ $workLocation->address ?? '' }}"
                        data-radius="{{ $workLocation->radius ?? '' }}"
                        @selected((string) old('work_location_id', $attendanceLocationLog->work_location_id ?? $attendance->employee?->work_location_id ?? '') === (string) $workLocation->id)>
                    {{ $workLocation->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6 mt-3">
        <label class="form-label d-flex align-items-center gap-1">
            Status
            <i class="fas fa-info-circle text-secondary text-xs" data-bs-toggle="tooltip" title="Pilih status kehadiran sesuai kondisi karyawan pada hari tersebut"></i>
        </label>
        <select name="status" class="form-control" required data-testid="attendance-status-select">
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $attendance->status ?? 'present') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-12 mt-3">
        <div class="card border shadow-none mb-0">
            <div class="card-body p-3">
                <p class="text-sm mb-1">Lokasi Kerja Terpilih</p>
                <h6 class="mb-1" id="selected-work-location-name">Belum ada lokasi kerja dipilih.</h6>
                <p class="text-xs text-secondary mb-1" id="selected-work-location-address"></p>
                <p class="text-xs text-secondary mb-0" id="selected-work-location-radius"></p>
            </div>
        </div>
    </div>

    <div class="col-12 mt-3">
        <label class="form-label">Tanggal</label>
        <input type="date" name="date" class="form-control" value="{{ old('date', isset($attendance->date) ? \Illuminate\Support\Carbon::parse($attendance->date)->format('Y-m-d') : '') }}" required>
    </div>

    <div class="col-md-6 mt-3">
        <label class="form-label">Check In</label>
        <input type="time" name="check_in" class="form-control" value="{{ old('check_in', $attendance->check_in ?? '') }}">
    </div>
    <div class="col-md-6 mt-3">
        <label class="form-label">Check Out</label>
        <input type="time" name="check_out" class="form-control" value="{{ old('check_out', $attendance->check_out ?? '') }}">
    </div>

    @if ($attendanceLocationLog?->check_in_photo_path || $attendanceLocationLog?->check_out_photo_path)
        <div class="col-12 mt-3">
            <div class="card border shadow-none mb-0">
                <div class="card-body p-3">
                    <p class="text-sm font-weight-bold mb-1">Bukti Foto Self-Service</p>
                    <p class="text-xs text-secondary mb-2">Foto ini tersimpan dari kamera live karyawan saat menekan tombol absen.</p>
                    <div class="d-flex flex-wrap gap-2">
                        @if ($attendanceLocationLog?->check_in_photo_path)
                            <a href="{{ \Illuminate\Support\Facades\Storage::url($attendanceLocationLog->check_in_photo_path) }}" target="_blank" class="btn btn-outline-success btn-sm mb-0">
                                <i class="fas fa-camera me-1"></i> Lihat Foto Masuk
                            </a>
                        @endif
                        @if ($attendanceLocationLog?->check_out_photo_path)
                            <a href="{{ \Illuminate\Support\Facades\Storage::url($attendanceLocationLog->check_out_photo_path) }}" target="_blank" class="btn btn-outline-info btn-sm mb-0">
                                <i class="fas fa-camera me-1"></i> Lihat Foto Pulang
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="col-md-6 mt-3">
        <label class="form-label d-flex align-items-center gap-1">
            Device Latitude
            <i class="fas fa-info-circle text-secondary text-xs" data-bs-toggle="tooltip" title="Koordinat perangkat diperlukan untuk validasi jarak ke lokasi kerja"></i>
        </label>
        <input type="number" step="0.0000001" name="latitude" id="attendance-latitude" class="form-control" value="{{ old('latitude', $attendanceLocationLog->latitude ?? '') }}" placeholder="Contoh: -6.1754">
    </div>
    <div class="col-md-6 mt-3">
        <label class="form-label d-flex align-items-center gap-1">
            Device Longitude
            <i class="fas fa-info-circle text-secondary text-xs" data-bs-toggle="tooltip" title="Koordinat perangkat diperlukan untuk validasi jarak ke lokasi kerja"></i>
        </label>
        <input type="number" step="0.0000001" name="longitude" id="attendance-longitude" class="form-control" value="{{ old('longitude', $attendanceLocationLog->longitude ?? '') }}" placeholder="Contoh: 106.8272">
    </div>
    <div class="col-12 mt-3">
        <span class="text-xs text-secondary" id="attendance-location-status">Koordinat perangkat akan terisi otomatis jika akses lokasi diizinkan.</span>
    </div>
</div>

@once
    @push('scripts')
        <script>
            window.addEventListener('load', function () {
                var tenantSelect = document.querySelector('[data-testid="attendance-tenant-select"]');
                var employeeSelect = document.querySelector('select[name="employee_id"]');
                var workLocationSelect = document.querySelector('[data-testid="attendance-work-location-select"]');
                var locationName = document.getElementById('selected-work-location-name');
                var locationAddress = document.getElementById('selected-work-location-address');
                var locationRadius = document.getElementById('selected-work-location-radius');
                var latitudeInput = document.getElementById('attendance-latitude');
                var longitudeInput = document.getElementById('attendance-longitude');
                var detectButton = document.getElementById('detect-attendance-location');
                var detectStatus = document.getElementById('attendance-location-status');

                function filterOptionsByTenant(selectElement, selectedTenantId) {
                    if (!selectElement) {
                        return;
                    }

                    Array.from(selectElement.options).forEach(function (option, index) {
                        if (index === 0) {
                            option.hidden = false;
                            option.disabled = false;
                            return;
                        }

                        var optionTenantId = option.dataset.tenantId;
                        var matchesTenant = !selectedTenantId || optionTenantId === selectedTenantId;

                        option.hidden = !matchesTenant;
                        option.disabled = !matchesTenant;

                        if (!matchesTenant && option.selected) {
                            selectElement.value = '';
                        }
                    });
                }

                function updateWorkLocationPanel() {
                    if (!workLocationSelect) {
                        return;
                    }

                    var selectedOption = workLocationSelect.options[workLocationSelect.selectedIndex];
                    var workLocationName = selectedOption && selectedOption.value ? selectedOption.text : '';
                    var workLocationAddress = selectedOption ? selectedOption.dataset.address : '';
                    var workLocationRadius = selectedOption ? selectedOption.dataset.radius : '';

                    locationName.textContent = workLocationName || 'Belum ada lokasi kerja dipilih.';
                    locationAddress.textContent = workLocationAddress || '';
                    locationRadius.textContent = workLocationRadius ? 'Radius validasi: ' + workLocationRadius + ' meter' : '';
                }

                function syncAssignedWorkLocation() {
                    if (!employeeSelect || !workLocationSelect) {
                        return;
                    }

                    var selectedOption = employeeSelect.options[employeeSelect.selectedIndex];
                    var assignedWorkLocationId = selectedOption ? selectedOption.dataset.assignedWorkLocationId : '';

                    if (assignedWorkLocationId) {
                        workLocationSelect.value = assignedWorkLocationId;
                    }

                    updateWorkLocationPanel();
                }

                function setCoordinates(position) {
                    latitudeInput.value = position.coords.latitude.toFixed(7);
                    longitudeInput.value = position.coords.longitude.toFixed(7);
                    detectStatus.textContent = 'Koordinat perangkat berhasil ditangkap.';
                }

                function setLocationError(error) {
                    detectStatus.textContent = error || 'Tidak dapat menangkap koordinat perangkat, silakan izinkan akses lokasi';
                }

                function detectCoordinates() {
                    if (!navigator.geolocation) {
                        setLocationError('Tidak dapat menangkap koordinat perangkat, silakan izinkan akses lokasi');
                        return;
                    }

                    detectStatus.textContent = 'Sedang menangkap koordinat perangkat...';

                    navigator.geolocation.getCurrentPosition(
                        setCoordinates,
                        function () {
                            setLocationError('Tidak dapat menangkap koordinat perangkat, silakan izinkan akses lokasi');
                        },
                        {
                            enableHighAccuracy: true,
                            timeout: 10000,
                        }
                    );
                }

                if (tenantSelect) {
                    filterOptionsByTenant(employeeSelect, tenantSelect.value);
                    filterOptionsByTenant(workLocationSelect, tenantSelect.value);

                    tenantSelect.addEventListener('change', function () {
                        filterOptionsByTenant(employeeSelect, tenantSelect.value);
                        filterOptionsByTenant(workLocationSelect, tenantSelect.value);
                        syncAssignedWorkLocation();
                    });
                }

                updateWorkLocationPanel();
                syncAssignedWorkLocation();

                if (employeeSelect) {
                    employeeSelect.addEventListener('change', syncAssignedWorkLocation);
                }

                if (workLocationSelect) {
                    workLocationSelect.addEventListener('change', updateWorkLocationPanel);
                }

                if (detectButton) {
                    detectButton.addEventListener('click', detectCoordinates);
                }
            });
        </script>
    @endpush
@endonce
