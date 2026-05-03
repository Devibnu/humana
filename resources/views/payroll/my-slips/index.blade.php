@extends('layouts.user_type.auth')

@section('content')

@php
    $rupiah = fn ($value) => 'Rp '.number_format((float) $value, 0, ',', '.');
    $netSalary = fn ($payroll) => (float) ($payroll->monthly_salary ?? $payroll->daily_wage ?? 0)
        + (float) ($payroll->allowance_transport ?? 0)
        + (float) ($payroll->allowance_meal ?? 0)
        + (float) ($payroll->allowance_health ?? 0)
        + (float) ($payroll->overtime_pay ?? 0)
        - (float) ($payroll->deduction_tax ?? 0)
        - (float) ($payroll->deduction_bpjs ?? 0)
        - (float) ($payroll->deduction_loan ?? 0)
        - (float) ($payroll->deduction_attendance ?? 0);
@endphp

<div class="row">
    <div class="col-12">
        <x-flash-messages />
        <div class="card mx-4 mb-4 shadow-xs">
            <div class="card-header pb-0">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <h5 class="mb-1">Slip Gaji Saya</h5>
                        <p class="text-sm text-secondary mb-0">Lihat riwayat slip gaji pribadi untuk {{ $employee->name }}.</p>
                    </div>
                    <span class="badge bg-gradient-info">{{ $payslips->total() }} slip</span>
                </div>
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                @if($payslips->isEmpty())
                    <div class="text-center py-5" data-testid="my-payslips-empty-state">
                        <i class="fas fa-file-invoice-dollar fa-3x text-secondary mb-3"></i>
                        <p class="text-secondary mb-1">Belum ada slip gaji yang tersedia.</p>
                        <p class="text-sm text-secondary mb-0">Slip akan muncul setelah payroll karyawan Anda dibuat oleh HR.</p>
                    </div>
                @else
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0" data-testid="my-payslips-table">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4 text-start">Periode</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Gaji Pokok</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Tunjangan & Lembur</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Potongan</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Take Home Pay</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($payslips as $payroll)
                                    @php
                                        $allowances = (float) ($payroll->allowance_transport ?? 0)
                                            + (float) ($payroll->allowance_meal ?? 0)
                                            + (float) ($payroll->allowance_health ?? 0)
                                            + (float) ($payroll->overtime_pay ?? 0);
                                        $deductions = (float) ($payroll->deduction_tax ?? 0)
                                            + (float) ($payroll->deduction_bpjs ?? 0)
                                            + (float) ($payroll->deduction_loan ?? 0)
                                            + (float) ($payroll->deduction_attendance ?? 0);
                                    @endphp
                                    <tr>
                                        <td class="ps-4 text-start">
                                            <div class="py-2">
                                                <h6 class="mb-0 text-sm">{{ $payroll->period_start && $payroll->period_end ? $payroll->period_start->format('d M Y').' - '.$payroll->period_end->format('d M Y') : 'Periode belum diatur' }}</h6>
                                                <p class="text-xs text-secondary mb-0">{{ $employee->employee_code ?? 'Tanpa kode karyawan' }}</p>
                                            </div>
                                        </td>
                                        <td class="text-start">
                                            <p class="text-sm font-weight-bold mb-0">{{ $rupiah($payroll->monthly_salary ?? $payroll->daily_wage ?? 0) }}</p>
                                        </td>
                                        <td class="text-start">
                                            <span class="badge bg-gradient-success">{{ $rupiah($allowances) }}</span>
                                        </td>
                                        <td class="text-start">
                                            <span class="badge bg-gradient-danger">{{ $rupiah($deductions) }}</span>
                                        </td>
                                        <td class="text-start">
                                            <h6 class="mb-0">{{ $rupiah($netSalary($payroll)) }}</h6>
                                        </td>
                                        <td class="text-start">
                                            <a href="{{ route('my-payslips.show', $payroll) }}" class="btn btn-outline-dark btn-sm mb-0" data-testid="btn-open-my-payslip-{{ $payroll->id }}">
                                                <i class="fas fa-eye me-1"></i> Lihat Slip
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="px-4 pt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <p class="text-sm text-secondary mb-0">Menampilkan {{ $payslips->count() }} dari total {{ $payslips->total() }} slip.</p>
                        {{ $payslips->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
