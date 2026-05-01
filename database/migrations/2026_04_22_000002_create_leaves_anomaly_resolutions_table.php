<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leaves_anomaly_resolutions', function (Blueprint $table) {
            $table->id();
            $table->string('anomaly_id')->unique();
            $table->foreignId('manager_id')->constrained('users')->cascadeOnDelete();
            $table->text('resolution_note');
            $table->string('resolution_action', 100);
            $table->timestamp('resolved_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaves_anomaly_resolutions');
    }
};