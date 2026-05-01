<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lembur_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->enum('role_pengaju', ['karyawan', 'atasan'])->default('karyawan');
            $table->boolean('butuh_persetujuan')->default(true);
            $table->enum('tipe_tarif', ['per_jam', 'multiplier', 'tetap'])->default('per_jam');
            $table->decimal('nilai_tarif', 12, 2)->nullable();
            $table->decimal('multiplier', 4, 2)->default(1.5);
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lembur_settings');
    }
};
