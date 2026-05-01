{{-- Flash success message --}}
@if (session('success'))
    <div class="alert alert-success text-white mx-4 mt-2" role="alert">
        <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
    </div>
@endif

{{-- Flash error message --}}
@if (session('error'))
    <div class="alert alert-danger text-white mx-4 mt-2" role="alert">
        <i class="fas fa-exclamation-circle me-1"></i> {{ session('error') }}
    </div>
@endif

{{-- Validation errors --}}
@if ($errors->any())
    <div class="alert alert-danger text-white mx-4 mt-2" role="alert">
        <i class="fas fa-exclamation-triangle me-1"></i>
        <ul class="mb-0 mt-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
