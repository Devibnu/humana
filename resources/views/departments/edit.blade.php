@extends('layouts.user_type.auth')

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card mb-4 mx-4 shadow-xs" data-testid="departments-edit-card">
            <div class="card-header pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-1">Edit Departemen</h5>
                    <p class="text-sm text-secondary mb-0">Perbarui nama, tenant, kode internal, dan status operasional departemen.</p>
                </div>
                <a href="{{ route('departments.index') }}" class="btn btn-light btn-sm mb-0"><i class="fas fa-times me-1"></i> Batal</a>
            </div>
            <div class="card-body">
                <x-flash-messages />

                <form action="{{ route('departments.update', $department) }}" method="POST" data-testid="departments-edit-form">
                    @csrf
                    @method('PUT')
                    @include('departments._form')
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('departments.index') }}" class="btn btn-light mb-0"><i class="fas fa-times me-1"></i> Batal</a>
                        <button type="submit" class="btn bg-gradient-primary mb-0"><i class="fas fa-save me-1"></i> Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection