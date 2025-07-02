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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId("school_id")->constrained("schools")->cascadeOnDelete();
            $table->foreignId("student_id")->constrained("students")->cascadeOnDelete();
            $table->foreignId('absence_permit_id')->nullable()->constrained('absence_permits');
            $table->foreignId("check_in_status_id")->constrained("check_in_statuses");
            $table->foreignId("check_out_status_id")->constrained("check_out_statuses");
            $table->foreignId("attendance_window_id")->constrained("attendance_windows")->cascadeOnDelete();
            $table->timestamp("check_in_time")->nullable();
            $table->timestamp("check_out_time")->nullable();
            $table->unique(['attendance_window_id', 'student_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
