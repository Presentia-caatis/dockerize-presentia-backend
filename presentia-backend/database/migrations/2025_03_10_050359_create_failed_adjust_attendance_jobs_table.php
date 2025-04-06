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
        Schema::create('failed_adjust_attendance_jobs', function (Blueprint $table) {
            $table->foreignId('student_id')->nullable()->constrained('students')->cascadeOnDelete();
            $table->foreignId('attendance_window_id')->nullable()->constrained('attendance_windows')->cascadeOnDelete();
            $table->json('upcoming_attendance_window_data')->nullable();
            $table->string('context')->nullable();
            $table->text('message');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('failed_adjust_attendance_jobs');
    }
};
