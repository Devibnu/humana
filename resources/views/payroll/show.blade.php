@extends('layouts.user_type.auth')

@section('content')

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card mx-4 shadow-xs">
                <div class="card-header pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="mb-1">Detail Payroll</h5>
                        <p class="text-sm text-secondary mb-0">Informasi payroll untuk {{ $payroll->employee?->name ?? 'karyawan' }}.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('payroll.edit', $payroll) }}" class="btn bg-gradient-warning btn-sm mb-0"><i class="fas fa-edit me-1"></i> Edit</a>
                        <a href="{{ route('payroll.index') }}" class="btn btn-light btn-sm mb-0"><i class="fas fa-arrow-left me-1"></i> Kembali</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="border border-radius-md p-3 h-100">
                                <p class="text-sm text-secondary mb-1">Karyawan</p>
                                <h6 class="mb-1">{{ $payroll->employee?->name ?? '-' }}</h6>
                                <p class="text-sm mb-0 text-secondary">{{ $payroll->employee?->employee_code ?? 'Tanpa kode karyawan' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border border-radius-md p-3 h-100">
                                <p class="text-sm text-secondary mb-1">Tenant</p>
                                <h6 class="mb-1">{{ $payroll->employee?->tenant?->name ?? '-' }}</h6>
                                <p class="text-sm mb-0 text-secondary">Periode payroll aktif</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border border-radius-md p-3 h-100">
                                <p class="text-sm text-secondary mb-1">Gaji Bulanan</p>
                                <h6 class="mb-0">{{ $payroll->monthly_salary !== null ? 'Rp '.number_format((float) $payroll->monthly_salary, 0, ',', '.') : '-' }}</h6>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border border-radius-md p-3 h-100">
                                <p class="text-sm text-secondary mb-1">Gaji Harian</p>
                                <h6 class="mb-0">{{ $payroll->daily_wage !== null ? 'Rp '.number_format((float) $payroll->daily_wage, 0, ',', '.') : '-' }}</h6>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border border-radius-md p-3 h-100">
                                <p class="text-sm text-secondary mb-1">Periode</p>
                                <h6 class="mb-0">{{ $payroll->period_start && $payroll->period_end ? $payroll->period_start.' - '.$payroll->period_end : 'Belum diatur' }}</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-12">
            <div class="card mx-4 mb-4 shadow-xs">
                <div class="card-header pb-0">
                    <h6 class="mb-1">Rincian Potongan</h6>
                    <p class="text-sm text-secondary mb-0">Ringkasan potongan yang diterapkan pada payroll ini.</p>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="border border-radius-md p-3 h-100">
                                <p class="text-sm text-secondary mb-1">Potongan Pajak</p>
                                <h6 class="mb-0">{{ $payroll->deduction_tax !== null ? 'Rp '.number_format((float) $payroll->deduction_tax, 0, ',', '.') : '-' }}</h6>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border border-radius-md p-3 h-100">
                                <p class="text-sm text-secondary mb-1">Potongan BPJS</p>
                                <h6 class="mb-0">{{ $payroll->deduction_bpjs !== null ? 'Rp '.number_format((float) $payroll->deduction_bpjs, 0, ',', '.') : '-' }}</h6>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border border-radius-md p-3 h-100">
                                <p class="text-sm text-secondary mb-1">Potongan Pinjaman</p>
                                <h6 class="mb-0">{{ $payroll->deduction_loan !== null ? 'Rp '.number_format((float) $payroll->deduction_loan, 0, ',', '.') : '-' }}</h6>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border border-radius-md p-3 h-100">
                                <p class="text-sm text-secondary mb-1">Potongan Absensi</p>
                                <h6 class="mb-0">{{ $payroll->deduction_attendance !== null ? 'Rp '.number_format((float) $payroll->deduction_attendance, 0, ',', '.') : '-' }}</h6>
                                <p class="text-sm text-secondary mt-2 mb-0">{{ $payroll->deduction_attendance_note ?? '-' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-12">
            <div class="card mx-4 mb-4 shadow-xs">
                <div class="card-header pb-0">
                    <h6 class="mb-1">Rincian Lembur</h6>
                    <p class="text-sm text-secondary mb-0">Ringkasan upah lembur yang dihitung otomatis dari absensi.</p>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="border border-radius-md p-3 h-100">
                                <p class="text-sm text-secondary mb-1">Total Upah Lembur</p>
                                <h6 class="mb-0">{{ $payroll->overtime_pay !== null ? 'Rp '.number_format((float) $payroll->overtime_pay, 0, ',', '.') : '-' }}</h6>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="border border-radius-md p-3 h-100">
                                <p class="text-sm text-secondary mb-1">Catatan Lembur</p>
                                <p class="text-sm mb-0">{{ $payroll->overtime_note ?? '-' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border border-radius-md p-3 h-100">
                                <p class="text-sm text-secondary mb-1">Ringkasan Lembur Disetujui</p>
                                <p class="text-sm mb-1">Total Jam: {{ rtrim(rtrim(number_format((float) $lemburTotalHours, 2, '.', ''), '0'), '.') }}</p>
                                <p class="text-sm mb-0">Total Nilai: Rp {{ number_format((float) $lemburTotalValue, 0, '.', ',') }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border border-radius-md p-3 h-100">
                                <p class="text-sm text-secondary mb-1">Daftar Lembur Periode Ini</p>
                                @if($approvedLemburs->isEmpty())
                                    <p class="text-sm mb-0 text-secondary">Tidak ada lembur disetujui dalam periode ini.</p>
                                @else
                                    <ul class="list-unstyled mb-0 text-sm">
                                        @foreach($approvedLemburs as $approvedLembur)
                                            <li class="mb-1">
                                                {{ $approvedLembur->waktu_mulai?->format('Y-m-d H:i') ?? '-' }} - {{ $approvedLembur->waktu_selesai?->format('Y-m-d H:i') ?? '-' }}
                                                ({{ rtrim(rtrim(number_format((float) ($approvedLembur->durasi_jam ?? 0), 2, '.', ''), '0'), '.') }} jam)
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-12">
            <div class="card mx-4 mb-4 shadow-xs">
                <div class="card-header pb-0">
                    <h6 class="mb-1">Cuti dalam Periode</h6>
                    <p class="text-sm text-secondary mb-0">Daftar cuti yang disetujui selama periode payroll ini.</p>
                </div>
                <div class="card-body">
                    @if($approvedLeaves->isEmpty())
                        <p class="text-sm text-secondary mb-0">Tidak ada cuti dalam periode ini.</p>
                    @else
                        <ul class="list-unstyled mb-0">
                            @foreach($approvedLeaves as $leave)
                                <li class="text-sm mb-1">
                                    <span class="fw-bold">{{ $leave->leaveType?->name ?? '-' }}</span>
                                    — {{ $leave->start_date }} s/d {{ $leave->end_date }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
