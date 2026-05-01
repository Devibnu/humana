@php
    $errorBag = 'editPosition'.$position->id;
    $submittedPositionId = old('edit_position_id');
    $usesOldInput = (string) $submittedPositionId === (string) $position->id;
@endphp

<div class="modal fade" id="editPositionModal{{ $position->id }}" tabindex="-1" aria-labelledby="editPositionLabel{{ $position->id }}" aria-hidden="true" data-testid="position-edit-modal-{{ $position->id }}">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPositionLabel{{ $position->id }}">Edit Posisi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form action="{{ route('departments.positions.update', [$department, $position]) }}" method="POST" data-testid="form-edit-position-{{ $position->id }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="edit_position_id" value="{{ $position->id }}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_position_name_{{ $position->id }}" class="form-label d-flex align-items-center gap-1">
                                Nama Posisi <span class="text-danger">*</span>
                                <i class="fas fa-info-circle text-secondary text-xs" data-bs-toggle="tooltip" title="Gunakan nama resmi sesuai struktur organisasi"></i>
                            </label>
                            <input type="text" class="form-control @error('name', $errorBag) is-invalid @enderror" id="edit_position_name_{{ $position->id }}" name="name" value="{{ $usesOldInput ? old('name', $position->name) : $position->name }}" required>
                            @error('name', $errorBag)
                                <small class="text-danger d-block mt-1">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_position_code_{{ $position->id }}" class="form-label d-flex align-items-center gap-1">
                                Kode Posisi
                                <i class="fas fa-info-circle text-secondary text-xs" data-bs-toggle="tooltip" title="Opsional, untuk kode internal"></i>
                            </label>
                            <input type="text" class="form-control @error('code', $errorBag) is-invalid @enderror" id="edit_position_code_{{ $position->id }}" name="code" value="{{ $usesOldInput ? old('code', $position->code) : $position->code }}" placeholder="MGR-01">
                            @error('code', $errorBag)
                                <small class="text-danger d-block mt-1">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 mb-0">
                            <label for="edit_position_description_{{ $position->id }}" class="form-label">Deskripsi</label>
                            <textarea class="form-control @error('description', $errorBag) is-invalid @enderror" id="edit_position_description_{{ $position->id }}" name="description" rows="4" placeholder="Tambahkan deskripsi singkat tanggung jawab posisi ini">{{ $usesOldInput ? old('description', $position->description) : $position->description }}</textarea>
                            @error('description', $errorBag)
                                <small class="text-danger d-block mt-1">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light mb-0" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i> Batal</button>
                    <button type="submit" class="btn btn-primary mb-0"><i class="fas fa-save me-1"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($errors->getBag($errorBag)->isNotEmpty())
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modalElement = document.getElementById('editPositionModal{{ $position->id }}');
            if (modalElement && typeof bootstrap !== 'undefined') {
                bootstrap.Modal.getOrCreateInstance(modalElement).show();
            }
        });
    </script>
@endif