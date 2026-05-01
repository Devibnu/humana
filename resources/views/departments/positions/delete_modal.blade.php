<div class="modal fade" id="deletePositionModal-{{ $position->id }}" tabindex="-1" aria-labelledby="deletePositionLabel-{{ $position->id }}" aria-hidden="true" data-testid="position-delete-modal-{{ $position->id }}">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deletePositionLabel-{{ $position->id }}">Konfirmasi Hapus Posisi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                Apakah Anda yakin ingin menghapus posisi <strong>{{ $position->name }}</strong>?
            </div>
            <div class="modal-footer">
                <form method="POST" action="{{ route('departments.positions.destroy', [$department->id, $position->id]) }}">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn btn-light mb-0" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-danger mb-0" data-testid="confirm-delete-position-{{ $position->id }}">
                        <i class="fas fa-trash me-1"></i> Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>