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
            $table->string('name');
            $table->enum('type', ['fingerprint', 'rfid', 'qr_code', 'face_recognition']);
            $table->string('username');
            $table->string('password');
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('get_url_credential_info');
            $table->string('post_url_authenticate');
            $table->string('post_url_credential_info');
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
