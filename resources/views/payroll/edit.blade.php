@extends('layouts.user_type.auth')

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card mx-4 mb-4 shadow-xs" data-testid="payroll-edit-card">
            <div class="card-header pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-1">Edit Payroll</h5>
                    <p class="text-sm text-secondary mb-0">Perbarui komponen payroll dengan struktur yang sama seperti input baru agar review nominal dan potongan lebih cepat.</p>
                </div>
                <a href="{{ route('payroll.index') }}" class="btn btn-light btn-sm mb-0"><i class="fas fa-arrow-left me-1"></i> Kembali</a>
            </div>
            <div class="card-body">
                <x-flash-messages />

                <form action="{{ route('payroll.update', $payroll) }}" method="POST" data-testid="payroll-edit-form">
                    @csrf
                    @method('PUT')

                    @include('payroll.partials.form-fields', [
                        'payroll' => $payroll,
                        'submitLabel' => 'Perbarui Payroll',
                    ])

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('payroll.index') }}" class="btn btn-light mb-0"><i class="fas fa-times me-1"></i> Batal</a>
                        <button type="submit" class="btn bg-gradient-primary mb-0">
                            <i class="fas fa-save me-1"></i> Perbarui Payroll
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection