<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('ktp_number', 20)->nullable()->after('phone');
            $table->string('kk_number', 20)->nullable()->after('ktp_number');
            $table->string('education', 20)->nullable()->after('kk_number');
            $table->date('dob')->nullable()->after('education');
            $table->string('gender', 10)->nullable()->after('dob');
            $table->text('address')->nullable()->after('gender');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['ktp_number', 'kk_number', 'education', 'dob', 'gender', 'address']);
        });
    }
};
