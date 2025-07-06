<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendance_sources', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['fingerprint', 'rfid', 'qr_code', 'face_recognition']);
            $table->string('username');
            $table->string('password');
            $table->string('token')->nullable();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('base_url');
            $table->unique('school_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_sources');
    }
};
