<div class="modal fade" id="addPositionModal" tabindex="-1" aria-labelledby="addPositionLabel" aria-hidden="true" data-testid="position-modal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPositionLabel">Tambah Posisi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form action="{{ route('departments.positions.store', $department) }}" method="POST" data-testid="form-add-position">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="position_name" class="form-label d-flex align-items-center gap-1">
                                Nama Posisi <span class="text-danger">*</span>
                                <i class="fas fa-info-circle text-secondary text-xs" data-bs-toggle="tooltip" title="Gunakan nama resmi sesuai struktur organisasi"></i>
                            </label>
                            <input type="text" class="form-control @error('name', 'addPosition') is-invalid @enderror" id="position_name" name="name" value="{{ old('name') }}" required>
                            @error('name', 'addPosition')
                                <small class="text-danger d-block mt-1">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="position_code" class="form-label d-flex align-items-center gap-1">
                                Kode Posisi
                                <i class="fas fa-info-circle text-secondary text-xs" data-bs-toggle="tooltip" title="Opsional, untuk kode internal"></i>
                            </label>
                            <input type="text" class="form-control @error('code', 'addPosition') is-invalid @enderror" id="position_code" name="code" value="{{ old('code') }}" placeholder="MGR-01">
                            @error('code', 'addPosition')
                                <small class="text-danger d-block mt-1">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 mb-0">
                            <label for="position_description" class="form-label">Deskripsi</label>
                            <textarea class="form-control @error('description', 'addPosition') is-invalid @enderror" id="position_description" name="description" rows="4" placeholder="Tambahkan deskripsi singkat tanggung jawab posisi ini">{{ old('description') }}</textarea>
                            @error('description', 'addPosition')
                                <small class="text-danger d-block mt-1">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light mb-0" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i> Batal</button>
                    <button type="submit" class="btn btn-primary mb-0"><i class="fas fa-save me-1"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($errors->addPosition->isNotEmpty())
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modalElement = document.getElementById('addPositionModal');
            if (modalElement && typeof bootstrap !== 'undefined') {
                bootstrap.Modal.getOrCreateInstance(modalElement).show();
            }
        });
    </script>
@endif