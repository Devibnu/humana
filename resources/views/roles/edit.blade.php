@extends('layouts.user_type.auth')

@section('content')

<div class="row">
    <div class="col-12">
        <x-flash-messages />

        <div class="card mx-4 mb-4 shadow-xs">
            <div class="card-header pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-1">Edit Role</h5>
                    <p class="text-sm text-secondary mb-0">Perbarui informasi role dan hak akses menu untuk <strong>{{ $role->name }}</strong>.</p>
                </div>
                <a href="{{ route('roles.index') }}" class="btn btn-light btn-sm mb-0">
                    <i class="fas fa-arrow-left me-1"></i> Kembali
                </a>
            </div>

            <div class="card-body">
                <form action="{{ route('roles.update', $role) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row g-4">
                        <div class="col-lg-5">
                            <div class="card border shadow-xs h-100">
                                <div class="card-header pb-0">
                                    <h6 class="mb-0">Informasi Role</h6>
                                    <p class="text-xs text-secondary mb-0">Nama role dan deskripsinya.</p>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Nama Role <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $role->name) }}" required>
                                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="mb-0">
                                        <label class="form-label">Deskripsi</label>
                                        <textarea name="description" rows="5" class="form-control @error('description') is-invalid @enderror">{{ old('description', $role->description) }}</textarea>
                                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-7">
                            <div class="card border shadow-xs h-100">
                                <div class="card-header pb-0">
                                    <h6 class="mb-0">Hak Akses Menu</h6>
                                    <p class="text-xs text-secondary mb-0">Pilih menu yang boleh dibuka oleh user dengan role ini.</p>
                                </div>
                                <div class="card-body">
                                    @foreach ($menuGroups as $group)
                                        <div class="mb-4">
                                            <h6 class="text-sm mb-3">{{ $group['label'] }}</h6>
                                            <div class="row g-3">
                                                @foreach ($group['menus'] as $menuKey => $menu)
                                                    <div class="col-md-6">
                                                        <label class="border rounded-3 p-3 d-flex align-items-start gap-3 w-100">
                                                            <input class="form-check-input mt-1" type="checkbox" name="permissions[]" value="{{ $menuKey }}" @checked(in_array($menuKey, old('permissions', $selectedPermissions), true))>
                                                            <span>
                                                                <span class="d-block text-sm font-weight-bold">{{ $menu['label'] }}</span>
                                                                <span class="d-block text-xs text-secondary">{{ $menu['description'] }}</span>
                                                            </span>
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('roles.index') }}" class="btn btn-light mb-0">Batal</a>
                        <button type="submit" class="btn bg-gradient-primary mb-0">
                            <i class="fas fa-save me-1"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection