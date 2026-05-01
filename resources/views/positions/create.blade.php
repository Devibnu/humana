@extends('layouts.user_type.auth')

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card mx-4 mb-4 shadow-xs">
            <div class="card-header pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-1">Tambah Posisi Baru</h5>
                    <p class="text-sm text-secondary mb-0">Tambahkan posisi baru dengan tenant, departemen, dan status operasional yang sesuai struktur HRIS.</p>
                </div>
                <a href="{{ route('positions.index') }}" class="btn btn-light btn-sm mb-0"><i class="fas fa-times me-1"></i> Batal</a>
            </div>
            <div class="card-body">
                <x-flash-messages />

                @if ($tenants->isEmpty() || $departments->isEmpty())
                    <div class="border border-secondary border-radius-md p-4 text-center bg-gray-100" data-testid="positions-create-empty-state">
                        <i class="fas fa-briefcase text-secondary fa-2x mb-3"></i>
                        <h6 class="mb-2">Belum ada tenant/departemen, silakan buat terlebih dahulu</h6>
                        <p class="text-sm text-secondary mb-0">Posisi membutuhkan tenant dan departemen agar relasi organisasi tetap konsisten.</p>
                    </div>
                @else
                    <form action="{{ route('positions.store') }}" method="POST" data-testid="positions-create-form">
                        @csrf
                        @include('positions._form')
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('positions.index') }}" class="btn btn-light mb-0"><i class="fas fa-times me-1"></i> Batal</a>
                            <button type="submit" class="btn btn-primary mb-0"><i class="fas fa-save me-1"></i> Simpan Posisi</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection