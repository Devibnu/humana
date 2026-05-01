@extends('layouts.user_type.auth')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card mx-4 mb-4 shadow-xs">
            <div class="card-header pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-1">Tambah Jenis Cuti</h5>
                    <p class="text-sm text-secondary mb-0">Tambahkan jenis cuti baru dengan aturan lampiran, persetujuan, dan alur approval yang jelas.</p>
                </div>
                <a href="{{ route('jenis-cuti.index') }}" class="btn btn-light btn-sm mb-0"><i class="fas fa-times me-1"></i> Batal</a>
            </div>
            <div class="card-body">
                <x-flash-messages />

                @if ($tenants->isEmpty())
                    <div class="border border-secondary border-radius-md p-4 text-center bg-gray-100" data-testid="leave-types-create-empty-state">
                        <i class="fas fa-layer-group text-secondary fa-2x mb-3"></i>
                        <h6 class="mb-2">Belum ada tenant, silakan buat terlebih dahulu</h6>
                        <p class="text-sm text-secondary mb-0">Jenis cuti membutuhkan tenant agar kebijakan cuti bisa diterapkan pada organisasi yang benar.</p>
                    </div>
                @else
                    <form action="{{ route('jenis-cuti.store') }}" method="POST" data-testid="leave-types-create-form">
                        @csrf
                        @include('jenis_cuti._form')
                        <div class="d-flex justify-content-end gap-2 mt-4 flex-wrap">
                            <a href="{{ route('jenis-cuti.index') }}" class="btn btn-light mb-0"><i class="fas fa-times me-1"></i> Batal</a>
                            <button type="submit" class="btn bg-gradient-primary mb-0"><i class="fas fa-save me-1"></i> Simpan Jenis Cuti</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
