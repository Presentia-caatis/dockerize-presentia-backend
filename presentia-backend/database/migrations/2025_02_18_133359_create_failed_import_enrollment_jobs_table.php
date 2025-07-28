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
        Schema::create('failed_import_enrollment_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('nisn')->nullable();           // Student NISN if available
            $table->unsignedBigInteger('student_id')->nullable(); // Student ID if available
            $table->unsignedBigInteger('school_id')->nullable();
            $table->unsignedBigInteger('semester_id')->nullable();
            $table->string('class_group_name')->nullable(); // Which class group was being imported
            $table->text('row_data')->nullable();           // The actual row content (JSON or text)
            $table->text('error_message')->nullable();      // What went wrong
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('failed_import_enrollment_jobs');
    }
};
