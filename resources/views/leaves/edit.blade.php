@extends('layouts.user_type.auth')

@section('content')

<div class="row">
    <div class="col-lg-8 col-12 mx-auto">
        <div class="card mb-4">
            <div class="card-header pb-0"><h5>Edit Leave Request</h5></div>
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
                <form action="{{ route('leaves.update', ['leaf' => $leave->getKey()]) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    @include('leaves._form')
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('leaves.index') }}" class="btn btn-light mb-0">Cancel</a>
                        <button type="submit" class="btn bg-gradient-primary mb-0">Update Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection