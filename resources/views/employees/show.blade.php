@extends('layouts.user_type.auth')

@section('content')
@php($currentUser = auth()->user())
@php($profileInitials = strtoupper(
    
    collect(preg_split('/\s+/', trim($employee->name ?? 'Karyawan')))
        ->filter()
        ->take(2)
        ->map(fn ($segment) => 
            
            
            
            
            mb_substr($segment, 0, 1)
        )
        ->implode('') ?: 'KR'
))
@php($attendanceCount = $attendances->count())
@php($leaveCount = $leaves->count())
@php($familyCount = $employee->familyMembers->count())
@php($bankCount = $employee->bankAccounts->count())
@php($employmentStatusLabel = $employee->employment_type === 'kontrak' ? 'Kontrak' : ($employee->employment_type === 'tetap' ? 'Tetap' : 'Belum diatur'))
@php($genderLabel = $employee->gender === 'male' ? 'Laki-laki' : ($employee->gender === 'female' ? 'Perempuan' : '—'))

<div class="row">
    <div class="col-12">

        <x-flash-messages />

        <div class="card mx-4 mb-4 shadow-xs border-0 overflow-hidden">
            <div class="card-body p-0">
                <div class="p-4" style="background: linear-gradient(135deg, #1f2937 0%, #334155 45%, #0f172a 100%);">
                    <div class="row g-4 align-items-center">
                        <div class="col-lg-8">
                            <div class="d-flex align-items-start gap-3 flex-wrap">
                                <div class="d-flex align-items-center justify-content-center text-white fw-bold" style="width: 72px; height: 72px; border-radius: 22px; background: rgba(255,255,255,0.14); font-size: 1.35rem; letter-spacing: 0.08em;">
                                    {{ $profileInitials }}
                                </div>
                                <div class="text-white">
                                    <p class="text-uppercase text-xs mb-2" style="letter-spacing: 0.16em; opacity: 0.72;">Profil Karyawan</p>
                                    <h4 class="mb-2 text-white">{{ $employee->name }}</h4>
                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                        <span class="badge bg-white text-dark">{{ $employee->position?->name ?? 'Posisi belum diatur' }}</span>
                                        <span class="badge" style="background: rgba(255,255,255,0.16); color: #fff;">{{ $employee->department?->name ?? 'Departemen belum diatur' }}</span>
                                        <span class="badge bg-gradient-{{ $employee->status === 'active' ? 'success' : 'secondary' }}">{{ $employee->status === 'active' ? 'Aktif' : 'Tidak Aktif' }}</span>
                                        <span class="badge" style="background: rgba(255,255,255,0.16); color: #fff;">{{ $employmentStatusLabel }}</span>
                                    </div>
                                        <div class="d-flex flex-column flex-md-row align-items-md-start gap-4 gap-xl-5 text-sm">
                                            <div class="pe-md-4" style="min-width: 0; max-width: 320px;">
                                                <span style="opacity: 0.72;">Email</span>
                                                <div class="font-weight-bold text-truncate" style="white-space: nowrap !important; overflow: hidden !important; text-overflow: ellipsis !important; overflow-wrap: normal !important; word-break: normal !important; hyphens: none !important;">{{ $employee->email }}</div>
                                            </div>
                                            <div class="ps-md-2">
                                                <span style="opacity: 0.72;">No. Telepon</span>
                                                <div class="font-weight-bold">{{ $employee->phone ?? 'Belum diisi' }}</div>
                                            </div>
                                        </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="d-flex justify-content-lg-end gap-2 flex-wrap">
                                <a href="{{ route('employees.edit', $employee) }}" class="btn bg-white text-dark btn-sm mb-0">
                                    <i class="fas fa-edit me-1"></i> Ubah Data
                                </a>
                                <a href="#data-keluarga" class="btn btn-outline-light btn-sm mb-0" onclick="document.getElementById('data-keluarga-tab')?.click()">
                                    <i class="fas fa-users me-1"></i> Keluarga
                                </a>
                                <a href="#informasi-keuangan" class="btn btn-outline-light btn-sm mb-0" onclick="document.getElementById('informasi-keuangan-tab')?.click()">
                                    <i class="fas fa-wallet me-1"></i> Rekening
                                </a>
                                <a href="{{ route('employees.index') }}" class="btn btn-outline-light btn-sm mb-0">
                                    <i class="fas fa-arrow-left me-1"></i> Kembali
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-4 bg-white">
                    <div class="row g-3">
                        <div class="col-xl-2 col-md-4 col-sm-6">
                            <div class="border border-radius-xl p-3 h-100">
                                <p class="text-xs text-uppercase text-secondary mb-1">NIK</p>
                                <p class="font-weight-bold mb-0">{{ $employee->employee_code }}</p>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 col-sm-6">
                            <div class="border border-radius-xl p-3 h-100">
                                <p class="text-xs text-uppercase text-secondary mb-1">Tenant</p>
                                <p class="font-weight-bold mb-0">{{ $employee->tenant?->name ?? '—' }}</p>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 col-sm-6">
                            <div class="border border-radius-xl p-3 h-100">
                                <p class="text-xs text-uppercase text-secondary mb-1">Lokasi Kerja</p>
                                <p class="font-weight-bold mb-0">{{ $employee->workLocation?->name ?? '—' }}</p>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 col-sm-6">
                            <div class="border border-radius-xl p-3 h-100">
                                <p class="text-xs text-uppercase text-secondary mb-1">Absensi</p>
                                <p class="font-weight-bold mb-0">{{ $attendanceCount }} catatan</p>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 col-sm-6">
                            <div class="border border-radius-xl p-3 h-100">
                                <p class="text-xs text-uppercase text-secondary mb-1">Keluarga</p>
                                <p class="font-weight-bold mb-0">{{ $familyCount }} anggota</p>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 col-sm-6">
                            <div class="border border-radius-xl p-3 h-100">
                                <p class="text-xs text-uppercase text-secondary mb-1">Rekening</p>
                                <p class="font-weight-bold mb-0">{{ $bankCount }} akun</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tab Navigation --}}
        <div class="card mx-4 mb-4 shadow-xs">
            <div class="card-header pb-0">
                <ul class="nav nav-tabs" id="employeeTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="data-karyawan-tab" data-bs-toggle="tab"
                            data-bs-target="#data-karyawan" type="button" role="tab" data-testid="tab-employee-data">
                            <i class="fas fa-id-card me-1"></i> Data Karyawan
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="data-keluarga-tab" data-bs-toggle="tab"
                            data-bs-target="#data-keluarga" type="button" role="tab" data-testid="tab-family">
                            <i class="fas fa-users me-1"></i> Data Keluarga
                            @if ($employee->familyMembers->isNotEmpty())
                                <span class="badge bg-gradient-info ms-1">{{ $employee->familyMembers->count() }}</span>
                            @endif
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="informasi-keuangan-tab" data-bs-toggle="tab"
                            data-bs-target="#informasi-keuangan" type="button" role="tab" data-testid="tab-bank">
                            <i class="fas fa-money-bill me-1"></i> Informasi Keuangan
                            @if ($employee->bankAccounts->isNotEmpty())
                                <span class="badge bg-gradient-success ms-1">{{ $employee->bankAccounts->count() }}</span>
                            @endif
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="attendance-tab" data-bs-toggle="tab"
                            data-bs-target="#attendance" type="button" role="tab" data-testid="tab-attendance">
                            <i class="fas fa-calendar-check me-1"></i> Riwayat Absensi
                            @if ($attendances->isNotEmpty())
                                <span class="badge bg-gradient-danger ms-1">{{ $attendances->count() }}</span>
                            @endif
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="leaves-tab" data-bs-toggle="tab"
                            data-bs-target="#leaves" type="button" role="tab" data-testid="tab-leaves">
                            <i class="fas fa-plane-departure me-1"></i> Riwayat Cuti
                            @if ($leaves->isNotEmpty())
                                <span class="badge bg-gradient-warning text-dark ms-1">{{ $leaves->count() }}</span>
                            @endif
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body">
                <div class="tab-content" id="employeeTabContent">

                    {{-- ============================
                         TAB: DATA PRIBADI
                    ============================= --}}
                    <div class="tab-pane fade show active" id="data-karyawan" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-lg-4">
                                <div class="card border shadow-xs h-100">
                                    <div class="card-header pb-0">
                                        <h6 class="mb-1">Identitas Pribadi</h6>
                                        <p class="text-xs text-secondary mb-0">Informasi dasar dan kependudukan karyawan.</p>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <p class="text-xs text-secondary text-uppercase mb-1">Nama Lengkap</p>
                                            <p class="font-weight-bold mb-0">{{ $employee->name }}</p>
                                        </div>
                                        <div class="mb-3">
                                            <p class="text-xs text-secondary text-uppercase mb-1">Jenis Kelamin</p>
                                            <p class="font-weight-bold mb-0">{{ $genderLabel }}</p>
                                        </div>
                                        <div class="mb-3">
                                            <p class="text-xs text-secondary text-uppercase mb-1">Tanggal Lahir</p>
                                            <p class="font-weight-bold mb-0">{{ optional($employee->dob)->format('d M Y') ?? '—' }}</p>
                                        </div>
                                        <div class="mb-3">
                                            <p class="text-xs text-secondary text-uppercase mb-1">Pendidikan Terakhir</p>
                                            <p class="font-weight-bold mb-0">{{ $employee->education ?? '—' }}</p>
                                        </div>
                                        <div class="mb-0">
                                            <p class="text-xs text-secondary text-uppercase mb-1">Status Pernikahan</p>
                                            <p class="font-weight-bold mb-0">{{ $maritalStatusOptions[$employee->marital_status] ?? '—' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="card border shadow-xs h-100">
                                    <div class="card-header pb-0">
                                        <h6 class="mb-1">Kontak & Dokumen</h6>
                                        <p class="text-xs text-secondary mb-0">Kontak aktif dan nomor dokumen utama.</p>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-4">
                                            <p class="text-xs text-secondary text-uppercase mb-1">Email</p>
                                            <p class="font-weight-bold mb-0 text-truncate" style="white-space:nowrap !important; overflow:hidden !important; text-overflow:ellipsis !important; max-width:100% !important; overflow-wrap:normal !important; word-break:normal !important; hyphens:none !important;">{{ $employee->email }}</p>
                                        </div>
                                        <div class="mb-4">
                                            <p class="text-xs text-secondary text-uppercase mb-1">No. Telepon</p>
                                            <p class="font-weight-bold mb-0">{{ $employee->phone ?? '—' }}</p>
                                        </div>
                                        <div class="mb-3">
                                            <p class="text-xs text-secondary text-uppercase mb-1">No. KTP</p>
                                            <p class="font-weight-bold mb-0">{{ $employee->ktp_number ?? '—' }}</p>
                                        </div>
                                        <div class="mb-3">
                                            <p class="text-xs text-secondary text-uppercase mb-1">No. Kartu Keluarga</p>
                                            <p class="font-weight-bold mb-0">{{ $employee->kk_number ?? '—' }}</p>
                                        </div>
                                        <div class="mb-0">
                                            <p class="text-xs text-secondary text-uppercase mb-1">Alamat</p>
                                            <p class="font-weight-bold mb-0">{{ $employee->address ?? '—' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="card border shadow-xs h-100">
                                    <div class="card-header pb-0">
                                        <h6 class="mb-1">Status Kerja & Sistem</h6>
                                        <p class="text-xs text-secondary mb-0">Posisi kerja aktif dan hubungan ke akun sistem.</p>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <p class="text-xs text-secondary text-uppercase mb-1">Tenant</p>
                                            <p class="font-weight-bold mb-0">{{ $employee->tenant?->name ?? '—' }}</p>
                                        </div>
                                        <div class="mb-3">
                                            <p class="text-xs text-secondary text-uppercase mb-1">Departemen</p>
                                            <p class="font-weight-bold mb-0">{{ $employee->department?->name ?? '—' }}</p>
                                        </div>
                                        <div class="mb-3">
                                            <p class="text-xs text-secondary text-uppercase mb-1">Posisi / Jabatan</p>
                                            <p class="font-weight-bold mb-0">{{ $employee->position?->name ?? '—' }}</p>
                                        </div>
                                        <div class="mb-3">
                                            <p class="text-xs text-secondary text-uppercase mb-1">Status Pekerjaan</p>
                                            <p class="font-weight-bold mb-0">
                                                <span class="badge bg-gradient-{{ $employee->employment_type === 'kontrak' ? 'warning' : 'success' }}">{{ $employmentStatusLabel }}</span>
                                            </p>
                                            @if ($employee->employment_type === 'kontrak')
                                                <p class="text-xs text-secondary mb-0 mt-2">
                                                    {{ optional($employee->contract_start_date)->format('d M Y') ?? '?' }}
                                                    sampai
                                                    {{ optional($employee->contract_end_date)->format('d M Y') ?? '?' }}
                                                </p>
                                            @endif
                                        </div>
                                        <div class="mb-3">
                                            <p class="text-xs text-secondary text-uppercase mb-1">Tanggal Mulai Kerja</p>
                                            <p class="font-weight-bold mb-0">{{ optional($employee->start_date)->format('d M Y') ?? '—' }}</p>
                                        </div>
                                        <div class="mb-0">
                                            <p class="text-xs text-secondary text-uppercase mb-1">Akun Terhubung</p>
                                            <p class="font-weight-bold mb-0">
                                                @if ($employee->user)
                                                    {{ $employee->user->name }}
                                                    <span class="text-secondary d-block">{{ $employee->user->email }}</span>
                                                @else
                                                    <span class="text-secondary">Belum dihubungkan</span>
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ============================
                         TAB: DATA KELUARGA
                    ============================= --}}
                    <div class="tab-pane fade" id="data-keluarga" role="tabpanel">
                        <div class="row g-3 mb-4">
                            <div class="col-lg-8">
                                <div class="card border shadow-xs h-100">
                                    <div class="card-body d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                        <div>
                                            <h6 class="mb-1">Data Keluarga</h6>
                                            <p class="text-sm text-secondary mb-0">Kelola pasangan, anak, dan keluarga inti yang terkait dengan data karyawan.</p>
                                        </div>
                                        @if ($currentUser && ($currentUser->isAdminHr() || $currentUser->isManager()))
                                        <button class="btn bg-gradient-primary btn-sm mb-0"
                                            data-bs-toggle="modal" data-bs-target="#addFamilyMemberModal"
                                            data-testid="btn-add-family">
                                            <i class="fas fa-users me-1"></i> Tambah Anggota Keluarga
                                        </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="card border shadow-xs h-100">
                                    <div class="card-body">
                                        <p class="text-xs text-uppercase text-secondary mb-1">Ringkasan</p>
                                        <h4 class="mb-1">{{ $familyCount }}</h4>
                                        <p class="text-sm text-secondary mb-0">anggota keluarga terdaftar untuk karyawan ini.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if ($employee->familyMembers->isEmpty())
                            <div class="card border shadow-xs" data-testid="family-empty-state">
                                <div class="card-body text-center py-5">
                                    <div class="d-inline-flex align-items-center justify-content-center mb-3" style="width: 72px; height: 72px; border-radius: 20px; background: #f1f5f9; color: #64748b;">
                                        <i class="fas fa-users fa-2x"></i>
                                    </div>
                                    <p class="text-dark font-weight-bold mb-1">Belum ada data keluarga</p>
                                    <p class="text-sm text-secondary mb-0">Klik tombol <strong>Tambah Anggota Keluarga</strong> untuk mulai melengkapi profil keluarga karyawan.</p>
                                </div>
                            </div>
                        @else
                            <div class="card border shadow-xs">
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0" data-testid="family-table">
                                            <thead class="bg-gray-100">
                                                <tr>
                                                    <th class="text-xs text-uppercase ps-3">Nama</th>
                                                    <th class="text-xs text-uppercase">Hubungan</th>
                                                    <th class="text-xs text-uppercase">Tgl. Lahir</th>
                                                    <th class="text-xs text-uppercase">Pendidikan</th>
                                                    <th class="text-xs text-uppercase">Pekerjaan</th>
                                                    <th class="text-xs text-uppercase">Status Nikah</th>
                                                    @if ($currentUser && ($currentUser->isAdminHr() || $currentUser->isManager()))
                                                    <th class="text-xs text-uppercase text-end pe-3">Aksi</th>
                                                    @endif
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($employee->familyMembers as $member)
                                                <tr>
                                                    <td class="ps-3">
                                                        <div class="font-weight-bold text-sm">{{ $member->name }}</div>
                                                    </td>
                                                    <td class="text-sm">
                                                        <span class="badge bg-gray-100 text-dark">{{ $member->relationshipLabel() }}</span>
                                                    </td>
                                                    <td class="text-sm">{{ optional($member->dob)->format('d M Y') ?? '—' }}</td>
                                                    <td class="text-sm">{{ $member->education ?? '—' }}</td>
                                                    <td class="text-sm">{{ $member->job ?? '—' }}</td>
                                                    <td class="text-sm">{{ $member->maritalStatusLabel() }}</td>
                                                    @if ($currentUser && ($currentUser->isAdminHr() || $currentUser->isManager()))
                                                    <td class="text-end pe-3">
                                                        <button class="btn btn-outline-primary btn-xs mb-0 me-1"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#editFamilyModal{{ $member->id }}">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <form method="POST"
                                                            action="{{ route('employees.family-members.destroy', [$employee, $member]) }}"
                                                            class="d-inline"
                                                            onsubmit="return confirm('Hapus anggota keluarga ini?')">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="btn btn-outline-danger btn-xs mb-0"
                                                                data-testid="btn-delete-family-{{ $member->id }}">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                    @endif
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            {{-- Edit Family Modals --}}
                            @foreach ($employee->familyMembers as $member)
                                @include('employees.family_members.edit_modal', ['familyMember' => $member, 'employee' => $employee])
                            @endforeach
                        @endif
                    </div>

                    {{-- ============================
                         TAB: INFORMASI KEUANGAN
                    ============================= --}}
                    <div class="tab-pane fade" id="informasi-keuangan" role="tabpanel">
                        <div class="row g-3 mb-4">
                            <div class="col-lg-8">
                                <div class="card border shadow-xs h-100">
                                    <div class="card-body d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                        <div>
                                            <h6 class="mb-1">Informasi Keuangan</h6>
                                            <p class="text-sm text-secondary mb-0">Simpan rekening aktif karyawan untuk kebutuhan payroll dan pembayaran internal.</p>
                                        </div>
                                        @if ($currentUser && ($currentUser->isAdminHr() || $currentUser->isManager()))
                                        <button class="btn bg-gradient-primary btn-sm mb-0"
                                            data-bs-toggle="modal" data-bs-target="#addBankModal"
                                            data-testid="btn-add-bank">
                                            <i class="fas fa-money-bill me-1"></i> Tambah Rekening
                                        </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="card border shadow-xs h-100">
                                    <div class="card-body">
                                        <p class="text-xs text-uppercase text-secondary mb-1">Ringkasan</p>
                                        <h4 class="mb-1">{{ $bankCount }}</h4>
                                        <p class="text-sm text-secondary mb-0">rekening bank tersimpan dan siap digunakan.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if ($employee->bankAccounts->isEmpty())
                            <div class="card border shadow-xs" data-testid="bank-empty-state">
                                <div class="card-body text-center py-5">
                                    <div class="d-inline-flex align-items-center justify-content-center mb-3" style="width: 72px; height: 72px; border-radius: 20px; background: #f1f5f9; color: #64748b;">
                                        <i class="fas fa-money-bill fa-2x"></i>
                                    </div>
                                    <p class="text-dark font-weight-bold mb-1">Belum ada rekening bank</p>
                                    <p class="text-sm text-secondary mb-0">Klik tombol <strong>Tambah Rekening</strong> untuk melengkapi data pembayaran karyawan.</p>
                                </div>
                            </div>
                        @else
                            <div class="card border shadow-xs">
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0" data-testid="bank-table">
                                            <thead class="bg-gray-100">
                                                <tr>
                                                    <th class="text-xs text-uppercase ps-3">Bank</th>
                                                    <th class="text-xs text-uppercase">No. Rekening</th>
                                                    <th class="text-xs text-uppercase">Nama Pemilik</th>
                                                    @if ($currentUser && ($currentUser->isAdminHr() || $currentUser->isManager()))
                                                    <th class="text-xs text-uppercase text-end pe-3">Aksi</th>
                                                    @endif
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($employee->bankAccounts as $account)
                                                <tr>
                                                    <td class="ps-3">
                                                        <div class="font-weight-bold text-sm">{{ $account->bank_name }}</div>
                                                    </td>
                                                    <td class="text-sm font-monospace">{{ $account->account_number }}</td>
                                                    <td class="text-sm">{{ $account->account_holder }}</td>
                                                    @if ($currentUser && ($currentUser->isAdminHr() || $currentUser->isManager()))
                                                    <td class="text-end pe-3">
                                                        <button class="btn btn-outline-primary btn-xs mb-0 me-1"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#editBankModal{{ $account->id }}">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <form method="POST"
                                                            action="{{ route('employees.bank-accounts.destroy', [$employee, $account]) }}"
                                                            class="d-inline"
                                                            onsubmit="return confirm('Hapus rekening bank ini?')">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="btn btn-outline-danger btn-xs mb-0"
                                                                data-testid="btn-delete-bank-{{ $account->id }}">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                    @endif
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            {{-- Edit Bank Account Modals --}}
                            @foreach ($employee->bankAccounts as $account)
                            <div class="modal fade" id="editBankModal{{ $account->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content border-0 shadow-lg">
                                        <div class="modal-header bg-gray-100 border-bottom-0">
                                            <div>
                                                <h6 class="modal-title mb-1">Ubah Rekening Bank</h6>
                                                <p class="text-xs text-secondary mb-0">Perbarui informasi rekening untuk payroll dan pembayaran internal.</p>
                                            </div>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST" action="{{ route('employees.bank-accounts.update', [$employee, $account]) }}">
                                            @csrf @method('PUT')
                                            <div class="modal-body">
                                                @include('employees._bank_form', ['ba' => $account])
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn bg-gradient-primary btn-sm">Simpan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        @endif
                    </div>

                    <div class="tab-pane fade" id="attendance" role="tabpanel">
                        <div class="row g-3 mb-4">
                            <div class="col-lg-7">
                                <div class="card border shadow-xs h-100">
                                    <div class="card-body d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                        <div>
                                            <h6 class="mb-1">Riwayat Absensi</h6>
                                            <p class="text-sm text-secondary mb-0">Pantau konsistensi kehadiran, jam kerja, dan lokasi check-in karyawan.</p>
                                        </div>
                                        @if ($currentUser && ($currentUser->isAdminHr() || $currentUser->isManager()))
                                            <div class="d-flex gap-2 flex-wrap">
                                                <a href="{{ route('attendances.employee.export.csv', $employee) }}" class="btn btn-outline-secondary btn-sm mb-0" data-bs-toggle="tooltip" title="Unduh data absensi individu untuk audit operasional" data-testid="btn-export-employee-attendance-csv">
                                                    <i class="fas fa-file-csv me-1"></i> Export CSV
                                                </a>
                                                <a href="{{ route('attendances.employee.export.xlsx', $employee) }}" class="btn btn-outline-success btn-sm mb-0" data-bs-toggle="tooltip" title="Unduh data absensi individu untuk audit operasional" data-testid="btn-export-employee-attendance-xlsx">
                                                    <i class="fas fa-file-excel me-1"></i> Export XLSX
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-5">
                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="card border shadow-xs h-100">
                                            <div class="card-body">
                                                <p class="text-xs text-uppercase text-secondary mb-1">Total Catatan</p>
                                                <h4 class="mb-0">{{ $attendanceCount }}</h4>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="card border shadow-xs h-100">
                                            <div class="card-body">
                                                <p class="text-xs text-uppercase text-secondary mb-1">Status Terakhir</p>
                                                <h6 class="mb-0">{{ $attendances->first() ? match($attendances->first()->status) { 'present' => 'Hadir', 'late' => 'Terlambat', 'leave' => 'Izin', 'sick' => 'Sakit', 'absent' => 'Alpha', default => '—' } : 'Belum ada' }}</h6>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if ($attendances->isEmpty())
                            <div class="card border shadow-xs" data-testid="attendance-empty-state">
                                <div class="card-body text-center py-5">
                                    <div class="d-inline-flex align-items-center justify-content-center mb-3" style="width: 72px; height: 72px; border-radius: 20px; background: #f1f5f9; color: #64748b;">
                                        <i class="fas fa-calendar-times fa-2x"></i>
                                    </div>
                                    <p class="text-dark font-weight-bold mb-1">Belum ada data absensi individu.</p>
                                    <p class="text-sm text-secondary mb-0">Data kehadiran karyawan akan tampil di tab ini setelah absensi dicatat.</p>
                                </div>
                            </div>
                        @else
                            <div class="card border shadow-xs">
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0" data-testid="attendance-table">
                                            <thead class="bg-gray-100">
                                                <tr>
                                                    <th class="text-xs text-uppercase ps-3">Tanggal</th>
                                                    <th class="text-xs text-uppercase">Status</th>
                                                    <th class="text-xs text-uppercase">Jam Masuk</th>
                                                    <th class="text-xs text-uppercase">Jam Keluar</th>
                                                    <th class="text-xs text-uppercase">Durasi Jam Kerja</th>
                                                    <th class="text-xs text-uppercase">Lokasi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($attendances as $attendanceRecord)
                                                    @php($attendanceLog = $attendanceRecord->attendanceLog)
                                                    @php($locationName = $attendanceLog?->workLocation?->name ?? $employee->workLocation?->name ?? '—')
                                                    @php($durationMinutes = ($attendanceRecord->check_in && $attendanceRecord->check_out)
                                                        ? max(0, (((int) substr($attendanceRecord->check_out, 0, 2) * 60) + (int) substr($attendanceRecord->check_out, 3, 2)) - (((int) substr($attendanceRecord->check_in, 0, 2) * 60) + (int) substr($attendanceRecord->check_in, 3, 2)))
                                                        : null)
                                                    @php($durationLabel = $durationMinutes !== null ? sprintf('%d jam %02d menit', intdiv($durationMinutes, 60), $durationMinutes % 60) : '—')
                                                    <tr>
                                                        <td class="ps-3">
                                                            <div class="font-weight-bold text-sm">{{ optional($attendanceRecord->date)->format('d M Y') ?? '—' }}</div>
                                                        </td>
                                                        <td class="text-sm">
                                                            <span class="badge {{ match($attendanceRecord->status) { 'present' => 'bg-gradient-success', 'late' => 'bg-gradient-warning', 'leave' => 'bg-warning text-dark', 'sick' => 'bg-info', 'absent' => 'bg-danger', default => 'bg-secondary' } }}">
                                                                {{ match($attendanceRecord->status) { 'present' => 'Hadir', 'late' => 'Terlambat', 'leave' => 'Izin', 'sick' => 'Sakit', 'absent' => 'Alpha', default => '—' } }}
                                                            </span>
                                                        </td>
                                                        <td class="text-sm">{{ $attendanceRecord->check_in ?? '—' }}</td>
                                                        <td class="text-sm">{{ $attendanceRecord->check_out ?? '—' }}</td>
                                                        <td class="text-sm">{{ $durationLabel }}</td>
                                                        <td class="text-sm">{{ $locationName }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="tab-pane fade" id="leaves" role="tabpanel">
                        @php($leaveStatusClasses = ['pending' => 'bg-warning text-dark', 'approved' => 'bg-success', 'rejected' => 'bg-danger'])
                        @php($leaveStatusLabels = ['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'])
                        <div class="row g-3 mb-4">
                            <div class="col-lg-7">
                                <div class="card border shadow-xs h-100">
                                    <div class="card-body d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                        <div>
                                            <h6 class="mb-1">Riwayat Cuti</h6>
                                            <p class="text-sm text-secondary mb-0">Lihat histori izin dan cuti yang pernah diajukan beserta status persetujuannya.</p>
                                        </div>
                                        @if ($currentUser && ($currentUser->isAdminHr() || $currentUser->isManager()))
                                            <div class="d-flex gap-2 flex-wrap">
                                                <a href="{{ route('leaves.employee.export.csv', $employee) }}" class="btn btn-outline-secondary btn-sm mb-0" data-bs-toggle="tooltip" title="Unduh data cuti individu untuk audit operasional" data-testid="btn-export-employee-leaves-csv">
                                                    <i class="fas fa-file-csv me-1"></i> Export CSV
                                                </a>
                                                <a href="{{ route('leaves.employee.export.xlsx', $employee) }}" class="btn btn-outline-success btn-sm mb-0" data-bs-toggle="tooltip" title="Unduh data cuti individu untuk audit operasional" data-testid="btn-export-employee-leaves-xlsx">
                                                    <i class="fas fa-file-excel me-1"></i> Export XLSX
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-5">
                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="card border shadow-xs h-100">
                                            <div class="card-body">
                                                <p class="text-xs text-uppercase text-secondary mb-1">Total Pengajuan</p>
                                                <h4 class="mb-0">{{ $leaveCount }}</h4>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="card border shadow-xs h-100">
                                            <div class="card-body">
                                                <p class="text-xs text-uppercase text-secondary mb-1">Status Terakhir</p>
                                                <h6 class="mb-0">{{ $leaves->first() ? ($leaveStatusLabels[$leaves->first()->status] ?? ucfirst($leaves->first()->status)) : 'Belum ada' }}</h6>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if ($leaves->isEmpty())
                            <div class="card border shadow-xs" data-testid="employee-leaves-empty-state">
                                <div class="card-body text-center py-5">
                                    <div class="d-inline-flex align-items-center justify-content-center mb-3" style="width: 72px; height: 72px; border-radius: 20px; background: #f1f5f9; color: #64748b;">
                                        <i class="fas fa-calendar-times fa-2x"></i>
                                    </div>
                                    <p class="text-dark font-weight-bold mb-1">Belum ada data cuti individu.</p>
                                    <p class="text-sm text-secondary mb-0">Riwayat cuti karyawan akan tampil di tab ini setelah permintaan cuti dicatat.</p>
                                </div>
                            </div>
                        @else
                            <div class="card border shadow-xs">
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0" data-testid="employee-leaves-table">
                                            <thead class="bg-gray-100">
                                                <tr>
                                                    <th class="text-xs text-uppercase ps-3">Jenis Cuti</th>
                                                    <th class="text-xs text-uppercase">Tanggal Mulai</th>
                                                    <th class="text-xs text-uppercase">Tanggal Selesai</th>
                                                    <th class="text-xs text-uppercase">Durasi (hari)</th>
                                                    <th class="text-xs text-uppercase">Status</th>
                                                    <th class="text-xs text-uppercase">Alasan</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($leaves as $leaveRecord)
                                                    <tr>
                                                        <td class="ps-3">
                                                            <div class="font-weight-bold text-sm">{{ $leaveRecord->leaveType?->name ?? '—' }}</div>
                                                        </td>
                                                        <td class="text-sm">{{ optional($leaveRecord->start_date)->format('d M Y') ?? '—' }}</td>
                                                        <td class="text-sm">{{ optional($leaveRecord->end_date)->format('d M Y') ?? '—' }}</td>
                                                        <td class="text-sm">{{ $leaveRecord->duration ?? 0 }} hari</td>
                                                        <td class="text-sm">
                                                            <span class="badge {{ $leaveStatusClasses[$leaveRecord->status] ?? 'bg-secondary' }}">{{ $leaveStatusLabels[$leaveRecord->status] ?? ucfirst($leaveRecord->status) }}</span>
                                                        </td>
                                                        <td class="text-sm">{{ $leaveRecord->reason ?? '—' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                </div>{{-- /tab-content --}}
            </div>{{-- /card-body --}}
        </div>{{-- /card --}}
    </div>
</div>

@include('employees.family_members.modal', ['employee' => $employee])

{{-- Modal: Tambah Rekening Bank --}}
<div class="modal fade" id="addBankModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-gray-100 border-bottom-0">
                <div>
                    <h6 class="modal-title mb-1">Tambah Rekening Bank</h6>
                    <p class="text-xs text-secondary mb-0">Simpan rekening baru yang akan digunakan untuk kebutuhan pembayaran.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('employees.bank-accounts.store', $employee) }}"
                data-testid="form-add-bank">
                @csrf
                <div class="modal-body">
                    @include('employees._bank_form', ['ba' => null])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn bg-gradient-primary btn-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var tabButtons = Array.from(document.querySelectorAll('#employeeTabs [data-bs-toggle="tab"]'));

        if (tabButtons.length === 0) {
            return;
        }

        function activateTabByHash(hash) {
            if (!hash || !hash.startsWith('#')) {
                return;
            }

            var matchingButton = tabButtons.find(function (button) {
                return button.getAttribute('data-bs-target') === hash;
            });

            if (!matchingButton) {
                return;
            }

            matchingButton.click();
        }

        setTimeout(function () {
            activateTabByHash(window.location.hash);
        }, 50);

        setTimeout(function () {
            activateTabByHash(window.location.hash);
        }, 300);

        tabButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                var target = button.getAttribute('data-bs-target');

                if (!target) {
                    return;
                }

                var nextUrl = window.location.pathname + window.location.search + target;
                window.history.replaceState(null, '', nextUrl);
            });
        });

        window.addEventListener('hashchange', function () {
            activateTabByHash(window.location.hash);
        });
    });
</script>
@endpush
