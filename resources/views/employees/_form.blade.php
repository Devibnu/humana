@if ($isTenantLocked)
    <div class="alert alert-info text-white mx-0 mb-4">Tenant mengikuti akun Anda. Opsi posisi, departemen, dan lokasi kerja dibatasi sesuai tenant yang sama.</div>
@endif

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border shadow-xs h-100">
            <div class="card-header pb-0">
                <h6 class="mb-0">Informasi Personal</h6>
                <p class="text-xs text-secondary mb-0">Identitas karyawan, data kependudukan, dan kontak.</p>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', $employee->name ?? '') }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">NIK / Kode Karyawan <span class="text-danger">*</span></label>
                        <input type="text" name="employee_code" class="form-control @error('employee_code') is-invalid @enderror"
                            value="{{ old('employee_code', $employee->employee_code ?? '') }}" required placeholder="Contoh: EMP-001">
                        @error('employee_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label d-flex align-items-center gap-1">
                            No. KTP
                            <i class="fas fa-info-circle text-secondary text-xs" data-bs-toggle="tooltip" title="Nomor KTP 16 digit. Harus unik dalam satu tenant."></i>
                        </label>
                        <input type="text" name="ktp_number" class="form-control @error('ktp_number') is-invalid @enderror"
                            value="{{ old('ktp_number', $employee->ktp_number ?? '') }}" maxlength="20" placeholder="16 digit KTP">
                        @error('ktp_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label d-flex align-items-center gap-1">
                            No. Kartu Keluarga
                            <i class="fas fa-info-circle text-secondary text-xs" data-bs-toggle="tooltip" title="Nomor Kartu Keluarga (KK) 16 digit."></i>
                        </label>
                        <input type="text" name="kk_number" class="form-control @error('kk_number') is-invalid @enderror"
                            value="{{ old('kk_number', $employee->kk_number ?? '') }}" maxlength="20" placeholder="16 digit KK">
                        @error('kk_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Pendidikan Terakhir</label>
                        <select name="education" class="form-control @error('education') is-invalid @enderror">
                            <option value="">- Pilih -</option>
                            @foreach (['SD', 'SMP', 'SMA', 'SMK', 'D3', 'S1', 'S2', 'S3'] as $edu)
                                <option value="{{ $edu }}" @selected(old('education', $employee->education ?? '') === $edu)>{{ $edu }}</option>
                            @endforeach
                        </select>
                        @error('education')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tanggal Lahir</label>
                        <input type="date" name="dob" class="form-control @error('dob') is-invalid @enderror"
                            value="{{ old('dob', optional($employee->dob ?? null)->format('Y-m-d')) }}">
                        @error('dob')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Jenis Kelamin</label>
                        <select name="gender" class="form-control @error('gender') is-invalid @enderror">
                            <option value="">- Pilih -</option>
                            <option value="male" @selected(old('gender', $employee->gender ?? '') === 'male')>Laki-laki</option>
                            <option value="female" @selected(old('gender', $employee->gender ?? '') === 'female')>Perempuan</option>
                        </select>
                        @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status Pernikahan</label>
                        <select name="marital_status" class="form-control @error('marital_status') is-invalid @enderror">
                            <option value="">- Pilih -</option>
                            @foreach (['belum_menikah' => 'Belum Menikah', 'menikah' => 'Menikah', 'cerai_hidup' => 'Cerai Hidup', 'cerai_mati' => 'Cerai Mati'] as $msValue => $msLabel)
                                <option value="{{ $msValue }}" @selected(old('marital_status', $employee->marital_status ?? '') === $msValue)>{{ $msLabel }}</option>
                            @endforeach
                        </select>
                        @error('marital_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Alamat</label>
                        <textarea name="address" rows="2" class="form-control @error('address') is-invalid @enderror" placeholder="Alamat lengkap karyawan">{{ old('address', $employee->address ?? '') }}</textarea>
                        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email', $employee->email ?? '') }}" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">No. Telepon</label>
                        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                            value="{{ old('phone', $employee->phone ?? '') }}" placeholder="08xx-xxxx-xxxx">
                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Foto Karyawan</label>
                        <input type="file" name="avatar" class="form-control @error('avatar') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp">
                        <small class="text-muted">JPG, PNG, WEBP maks. 2 MB.</small>
                        @error('avatar')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card border shadow-xs h-100">
            <div class="card-header pb-0">
                <h6 class="mb-0">Informasi Pekerjaan</h6>
                <p class="text-xs text-secondary mb-0">Tenant, department, posisi, jabatan, status, dan tanggal masuk.</p>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Tenant <span class="text-danger">*</span></label>
                    @if ($isTenantLocked)
                        <input type="hidden" name="tenant_id" value="{{ $scopedTenantId }}">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-gradient-warning" data-testid="tenant-locked-badge">Tenant Terkunci</span>
                            <small class="text-muted">Manager hanya bisa mengelola tenant sendiri.</small>
                        </div>
                        <input type="text" class="form-control" value="{{ $tenants->first()?->name ?? 'Tenant tidak ditemukan' }}" readonly>
                    @else
                        <select name="tenant_id" class="form-control @error('tenant_id') is-invalid @enderror" required data-testid="tenant-select">
                            <option value="">Pilih tenant</option>
                            @foreach ($tenants as $tenant)
                                <option value="{{ $tenant->id }}" @selected(old('tenant_id', $employee->tenant_id ?? '') == $tenant->id)>
                                    {{ $tenant->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('tenant_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @endif
                </div>
                <div class="mb-3">
                    <label class="form-label">Department</label>
                    <select name="department_id" class="form-control @error('department_id') is-invalid @enderror">
                        <option value="">Pilih department</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept->id }}" @selected((string) old('department_id', $employee->department_id ?? '') === (string) $dept->id)>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Posisi / Jabatan</label>
                    <select name="position_id" class="form-control @error('position_id') is-invalid @enderror">
                        <option value="">Pilih posisi</option>
                        @foreach ($positions as $position)
                            <option value="{{ $position->id }}" @selected((string) old('position_id', $employee->position_id ?? '') === (string) $position->id)>
                                {{ $position->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('position_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Level Karyawan <span class="text-danger">*</span></label>
                    <select name="role" class="form-control @error('role') is-invalid @enderror" required>
                        <option value="">Pilih level karyawan</option>
                        @foreach ($roles as $value => $label)
                            <option value="{{ $value }}" @selected(old('role', $employee->role ?? 'staff') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <div class="d-flex flex-wrap gap-3 mt-1">
                        @foreach ($statuses as $value => $label)
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status" id="employee_status_{{ $value }}" value="{{ $value }}" @checked(old('status', $employee->status ?? 'active') === $value)>
                                <label class="form-check-label" for="employee_status_{{ $value }}">
                                    <span class="badge bg-gradient-{{ $value === 'active' ? 'success' : 'secondary' }}">{{ $label }}</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                    @error('status')<div class="text-danger text-xs mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Status Pekerjaan</label>
                    <div class="d-flex flex-wrap gap-3 mt-1">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="employment_type" id="employment_type_tetap" value="tetap" data-testid="employment-type-tetap" @checked(old('employment_type', $employee->employment_type ?? 'tetap') === 'tetap') onclick="document.getElementById('contractDates').classList.add('d-none')">
                            <label class="form-check-label" for="employment_type_tetap">Tetap</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="employment_type" id="employment_type_kontrak" value="kontrak" data-testid="employment-type-kontrak" @checked(old('employment_type', $employee->employment_type ?? '') === 'kontrak') onclick="document.getElementById('contractDates').classList.remove('d-none')">
                            <label class="form-check-label" for="employment_type_kontrak">Kontrak</label>
                        </div>
                    </div>
                    @error('employment_type')<div class="text-danger text-xs mt-1">{{ $message }}</div>@enderror
                </div>
                <div id="contractDates" class="mb-3 {{ old('employment_type', $employee->employment_type ?? '') === 'kontrak' ? '' : 'd-none' }}">
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label">Tgl. Mulai Kontrak <span class="text-danger">*</span></label>
                            <input type="date" name="contract_start_date" class="form-control @error('contract_start_date') is-invalid @enderror" value="{{ old('contract_start_date', optional($employee->contract_start_date ?? null)->format('Y-m-d')) }}" data-testid="contract-start-date">
                            @error('contract_start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label">Tgl. Berakhir Kontrak <span class="text-danger">*</span></label>
                            <input type="date" name="contract_end_date" class="form-control @error('contract_end_date') is-invalid @enderror" value="{{ old('contract_end_date', optional($employee->contract_end_date ?? null)->format('Y-m-d')) }}" data-testid="contract-end-date">
                            @error('contract_end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="mb-0">
                    <label class="form-label">Tanggal Mulai Kerja</label>
                    <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', optional($employee->start_date ?? null)->format('Y-m-d')) }}">
                    @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-lg-6">
        <div class="card border shadow-xs h-100">
            <div class="card-header pb-0">
                <h6 class="mb-0">Lokasi Kerja</h6>
                <p class="text-xs text-secondary mb-0">Lokasi kerja dan radius absensi karyawan.</p>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label d-flex align-items-center gap-1">
                        Lokasi Kerja
                        <i class="fas fa-info-circle text-info text-xs" data-bs-toggle="tooltip" title="Validasi absensi menggunakan radius lokasi kerja yang dipilih."></i>
                    </label>
                    <select name="work_location_id" class="form-control @error('work_location_id') is-invalid @enderror">
                        <option value="">Pilih lokasi kerja</option>
                        @foreach ($workLocations as $workLocation)
                            <option value="{{ $workLocation->id }}" @selected((string) old('work_location_id', $employee->work_location_id ?? '') === (string) $workLocation->id)>
                                {{ $workLocation->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('work_location_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="d-flex align-items-start gap-2 border border-secondary border-radius-md p-3 bg-gray-100">
                    <i class="fas fa-map-marker-alt text-info mt-1" data-bs-toggle="tooltip" title="Attendance validation can use the assigned work location radius."></i>
                    <div>
                        <p class="text-sm font-weight-bold mb-1">Info Validasi Absensi</p>
                        <p class="text-sm text-secondary mb-0">Validasi check-in absensi menggunakan radius lokasi kerja yang dipilih. Pastikan lokasi sudah memiliki koordinat GPS dan radius yang tepat.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border shadow-xs h-100">
            <div class="card-header pb-0">
                <h6 class="mb-0">Koneksi Akun</h6>
                <p class="text-xs text-secondary mb-0">Hubungkan data karyawan ke akun pengguna sistem.</p>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Akun Pengguna</label>
                    <select name="user_id" class="form-control @error('user_id') is-invalid @enderror" data-testid="employee-user-select">
                        <option value="">— Tidak dihubungkan —</option>
                        @foreach ($employeeUsers as $employeeUser)
                            <option value="{{ $employeeUser->id }}" @selected((string) old('user_id', $employee->user_id ?? '') === (string) $employeeUser->id)>
                                {{ $employeeUser->name }} ({{ $employeeUser->email }})
                            </option>
                        @endforeach
                    </select>
                    @error('user_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                @if ($employeeUsers->isEmpty())
                    <div class="d-flex align-items-start gap-2 border border-warning border-radius-md p-3 bg-gray-100" data-testid="no-employee-users-notice">
                        <i class="fas fa-exclamation-triangle text-warning mt-1"></i>
                        <div>
                            <p class="text-sm font-weight-bold mb-1">Belum ada akun tersedia</p>
                            <p class="text-sm text-secondary mb-0">Tidak ada akun dengan role <strong>Employee</strong> di tenant ini. Buat akun terlebih dahulu, atau biarkan kosong untuk melanjutkan tanpa link akun.</p>
                        </div>
                    </div>
                @else
                    <p class="text-xs text-secondary mb-0">
                        <i class="fas fa-info-circle me-1"></i>
                        Hanya akun dengan role <strong>Employee</strong> dalam tenant yang sama yang tersedia.
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>
