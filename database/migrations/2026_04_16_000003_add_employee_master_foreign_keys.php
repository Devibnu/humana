<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('employees')
            ->whereNotNull('position_id')
            ->whereNotIn('position_id', DB::table('positions')->select('id'))
            ->update(['position_id' => null]);

        DB::table('employees')
            ->whereNotNull('department_id')
            ->whereNotIn('department_id', DB::table('departments')->select('id'))
            ->update(['department_id' => null]);

        Schema::table('employees', function (Blueprint $table) {
            $table->foreign('position_id')->references('id')->on('positions')->nullOnDelete();
            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['position_id']);
            $table->dropForeign(['department_id']);
        });
    }
};