@extends('layouts.user_type.auth')

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card mx-4 mb-4 shadow-xs" data-testid="payroll-create-card">
            <div class="card-header pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-1">Payroll Manual</h5>
                    <p class="text-sm text-secondary mb-0">Masukkan payroll dengan alur yang lebih terstruktur: pilih karyawan, tentukan rule, isi kompensasi, lalu lengkapi periode dan potongan.</p>
                </div>
                <a href="{{ route('payroll.index') }}" class="btn btn-light btn-sm mb-0"><i class="fas fa-times me-1"></i> Batal</a>
            </div>
            <div class="card-body">
                <x-flash-messages />

                @if ($employees->isEmpty())
                    <div class="border border-secondary border-radius-md p-4 text-center bg-gray-100" data-testid="payroll-create-empty-state">
                        <i class="fas fa-money-check-alt text-secondary fa-2x mb-3"></i>
                        <h6 class="mb-2">Belum ada karyawan untuk diproses payroll</h6>
                        <p class="text-sm text-secondary mb-0">Tambahkan data karyawan terlebih dahulu agar entry payroll bisa dilakukan.</p>
                    </div>
                @else
                    <form action="{{ route('payroll.store') }}" method="POST" data-testid="payroll-create-form">
                        @csrf

                        @include('payroll.partials.form-fields', [
                            'payroll' => null,
                            'submitLabel' => 'Simpan Payroll',
                        ])

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('payroll.index') }}" class="btn btn-light mb-0"><i class="fas fa-times me-1"></i> Batal</a>
                            <button type="submit" class="btn bg-gradient-primary mb-0" data-testid="btn-submit-payroll">
                                <i class="fas fa-save me-1"></i> Simpan Payroll
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection