@extends('layouts.user_type.auth')

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card mb-4 mx-4 shadow-xs" data-testid="positions-edit-card">
            <div class="card-header pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-1">Edit Posisi</h5>
                    <p class="text-sm text-secondary mb-0">Perbarui tenant, departemen, kode internal, dan status operasional posisi.</p>
                </div>
                <a href="{{ request('redirect_to', route('positions.index')) }}" class="btn btn-light btn-sm mb-0"><i class="fas fa-times me-1"></i> Batal</a>
            </div>
            <div class="card-body">
                <x-flash-messages />

                <form action="{{ route('positions.update', $position) }}" method="POST" data-testid="positions-edit-form">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="redirect_to" value="{{ old('redirect_to', request('redirect_to')) }}">
                    @include('positions._form')
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ request('redirect_to', route('positions.index')) }}" class="btn btn-light mb-0"><i class="fas fa-times me-1"></i> Batal</a>
                        <button type="submit" class="btn bg-gradient-primary mb-0"><i class="fas fa-save me-1"></i> Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection