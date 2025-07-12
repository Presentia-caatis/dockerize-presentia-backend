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
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semester_id')->constrained("semesters")->cascadeOnDelete();
            $table->foreignId('class_group_id')->constrained("class_groups")->cascadeOnDelete();
            $table->foreignId('student_id')->constrained("students")->cascadeOnDelete();
            $table->foreignId('school_id')->constrained("schools")->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
