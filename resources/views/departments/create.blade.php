@extends('layouts.user_type.auth')

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card mb-4 mx-4 shadow-xs">
            <div class="card-header pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-1">Tambah Departemen Baru</h5>
                    <p class="text-sm text-secondary mb-0">Tambahkan departemen baru dengan tenant, kode internal, dan status operasional.</p>
                </div>
                <a href="{{ route('departments.index') }}" class="btn btn-light btn-sm mb-0"><i class="fas fa-times me-1"></i> Batal</a>
            </div>
            <div class="card-body">
                <x-flash-messages />

                @if ($tenants->isEmpty())
                    <div class="border border-secondary border-radius-md p-4 text-center bg-gray-100" data-testid="departments-create-empty-state">
                        <i class="fas fa-building text-secondary fa-2x mb-3"></i>
                        <h6 class="mb-2">Belum ada tenant tersedia</h6>
                        <p class="text-sm text-secondary mb-0">Buat tenant terlebih dahulu sebelum menambahkan departemen baru.</p>
                    </div>
                @else
                    <form action="{{ route('departments.store') }}" method="POST" data-testid="departments-create-form">
                        @csrf
                        @include('departments._form')
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('departments.index') }}" class="btn btn-light mb-0"><i class="fas fa-times me-1"></i> Batal</a>
                            <button type="submit" class="btn bg-gradient-primary mb-0"><i class="fas fa-save me-1"></i> Simpan</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection