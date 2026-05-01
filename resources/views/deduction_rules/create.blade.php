@extends('layouts.user_type.auth')

@section('content')

<div class="row">
    <div class="col-12">

        <div class="card mx-4 mb-4 shadow-xs">
            <div class="card-header pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-1">Tambah Potongan</h5>
                    <p class="text-sm text-secondary mb-0">Tambahkan aturan potongan absensi baru dengan jam kerja, toleransi, dan skema rate payroll yang jelas.</p>
                </div>
                <a href="{{ route('deduction_rules.index') }}" class="btn btn-light btn-sm mb-0">
                    <i class="fas fa-times me-1"></i> Batal
                </a>
            </div>

            <div class="card-body">
                <x-flash-messages />

                <form action="{{ route('deduction_rules.store') }}" method="POST" data-testid="deduction-rules-create-form">
                    @csrf

                    @include('deduction_rules._form')

                    <div class="d-flex justify-content-end gap-2 mt-4 flex-wrap">
                        <a href="{{ route('deduction_rules.index') }}" class="btn btn-light mb-0"><i class="fas fa-times me-1"></i> Batal</a>
                        <button type="submit" class="btn bg-gradient-primary mb-0"><i class="fas fa-save me-1"></i> Simpan Aturan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection