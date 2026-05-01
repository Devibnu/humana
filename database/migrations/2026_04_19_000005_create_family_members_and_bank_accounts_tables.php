<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('name');
            $table->string('relationship', 30); // pasangan, anak, orang_tua, saudara
            $table->date('dob')->nullable();
            $table->string('education', 20)->nullable(); // SD, SMP, SMA, S1, dll.
            $table->string('job', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('bank_name', 100);
            $table->string('account_number', 30);
            $table->string('account_holder', 150);
            $table->timestamps();

            $table->unique(['employee_id', 'account_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('family_members');
    }
};
