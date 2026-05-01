{{-- Partial: _bank_form.blade.php
     Variables: $ba (BankAccount|null)
--}}
<div class="mb-3">
    <label class="form-label">Nama Bank <span class="text-danger">*</span></label>
    <input type="text" name="bank_name"
        class="form-control @error('bank_name') is-invalid @enderror"
        value="{{ old('bank_name', $ba?->bank_name ?? '') }}"
        placeholder="Contoh: BCA, BNI, Mandiri" required>
    @error('bank_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
    <label class="form-label">Nomor Rekening <span class="text-danger">*</span></label>
    <input type="text" name="account_number"
        class="form-control @error('account_number') is-invalid @enderror"
        value="{{ old('account_number', $ba?->account_number ?? '') }}"
        placeholder="Contoh: 1234567890" required maxlength="30">
    @error('account_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-0">
    <label class="form-label">Nama Pemilik Rekening <span class="text-danger">*</span></label>
    <input type="text" name="account_holder"
        class="form-control @error('account_holder') is-invalid @enderror"
        value="{{ old('account_holder', $ba?->account_holder ?? '') }}"
        placeholder="Sesuai nama di buku tabungan" required>
    @error('account_holder')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
