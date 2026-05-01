<div class="modal fade" id="editFamilyModal{{ $familyMember->id }}" tabindex="-1" aria-labelledby="editFamilyMemberLabel{{ $familyMember->id }}" aria-hidden="true" data-testid="family-member-edit-modal-{{ $familyMember->id }}">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-gray-100 border-bottom-0">
                <div>
                    <h5 class="modal-title mb-1" id="editFamilyMemberLabel{{ $familyMember->id }}">Edit Anggota Keluarga</h5>
                    <p class="text-xs text-secondary mb-0">Perbarui data keluarga agar tetap sinkron dengan dokumen terbaru.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form action="{{ route('family-members.update', [$employee, $familyMember]) }}" method="POST" data-testid="form-edit-family-{{ $familyMember->id }}">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_family_member_name_{{ $familyMember->id }}" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="edit_family_member_name_{{ $familyMember->id }}" name="name" value="{{ old('name', $familyMember->name) }}" required>
                            @error('name')
                                <small class="text-danger d-block mt-1">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_family_member_relationship_{{ $familyMember->id }}" class="form-label d-flex align-items-center gap-1">
                                Hubungan <span class="text-danger">*</span>
                                <i class="fas fa-info-circle text-secondary text-xs" data-bs-toggle="tooltip" title="Hubungan keluarga sesuai KK"></i>
                            </label>
                            <select class="form-select @error('relationship') is-invalid @enderror" id="edit_family_member_relationship_{{ $familyMember->id }}" name="relationship" required>
                                <option value="">-- Pilih Hubungan --</option>
                                @foreach ($familyModalRelationships as $value => $label)
                                    <option value="{{ $value }}" @selected(old('relationship', $familyMember->relationship) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('relationship')
                                <small class="text-danger d-block mt-1">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_family_member_dob_{{ $familyMember->id }}" class="form-label d-flex align-items-center gap-1">
                                Tanggal Lahir <span class="text-danger">*</span>
                                <i class="fas fa-info-circle text-secondary text-xs" data-bs-toggle="tooltip" title="Format: dd/mm/yyyy"></i>
                            </label>
                            <input type="date" class="form-control @error('dob') is-invalid @enderror" id="edit_family_member_dob_{{ $familyMember->id }}" name="dob" value="{{ old('dob', optional($familyMember->dob)->format('Y-m-d')) }}" required>
                            @error('dob')
                                <small class="text-danger d-block mt-1">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_family_member_education_{{ $familyMember->id }}" class="form-label">Pendidikan Terakhir</label>
                            <input type="text" class="form-control @error('education') is-invalid @enderror" id="edit_family_member_education_{{ $familyMember->id }}" name="education" value="{{ old('education', $familyMember->education) }}" placeholder="Contoh: SMA atau S1">
                            @error('education')
                                <small class="text-danger d-block mt-1">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_family_member_job_{{ $familyMember->id }}" class="form-label">Pekerjaan</label>
                            <input type="text" class="form-control @error('job') is-invalid @enderror" id="edit_family_member_job_{{ $familyMember->id }}" name="job" value="{{ old('job', $familyMember->job) }}" placeholder="Contoh: Karyawan Swasta">
                            @error('job')
                                <small class="text-danger d-block mt-1">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_family_member_marital_status_{{ $familyMember->id }}" class="form-label d-flex align-items-center gap-1">
                                Status Pernikahan <span class="text-danger">*</span>
                                <i class="fas fa-info-circle text-secondary text-xs" data-bs-toggle="tooltip" title="Pilih sesuai dokumen resmi"></i>
                            </label>
                            <select class="form-select @error('marital_status') is-invalid @enderror" id="edit_family_member_marital_status_{{ $familyMember->id }}" name="marital_status" required>
                                <option value="">-- Pilih Status --</option>
                                @foreach ($familyMaritalStatuses as $value => $label)
                                    <option value="{{ $value }}" @selected(old('marital_status', $familyMember->marital_status) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('marital_status')
                                <small class="text-danger d-block mt-1">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fa fa-save me-1"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>