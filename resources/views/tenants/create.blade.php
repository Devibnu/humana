@extends('layouts.user_type.auth')

@section('content')

<div class="row">
    <div class="col-lg-8 col-12 mx-auto">
        <div class="card mb-4">
            <div class="card-header pb-0">
                <h5>Tambah Tenant</h5>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger text-white">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('tenants.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @include('tenants._form')

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('tenants.index') }}" class="btn btn-light mb-0">Batal</a>
                        <button type="submit" class="btn bg-gradient-primary mb-0">Simpan Tenant</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection