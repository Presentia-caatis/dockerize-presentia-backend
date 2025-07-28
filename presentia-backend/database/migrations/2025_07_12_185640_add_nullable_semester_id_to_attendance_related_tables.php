<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('check_in_statuses', function (Blueprint $table) {
            $table->foreignId('semester_id')->nullable()->constrained('semesters')->nullOnDelete();
        });
        Schema::table('check_out_statuses', function (Blueprint $table) {
            $table->foreignId('semester_id')->nullable()->constrained('semesters')->nullOnDelete();
        });
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('semester_id')->nullable()->constrained('semesters')->nullOnDelete();
        });
        Schema::table('attendance_windows', function (Blueprint $table) {
            $table->foreignId('semester_id')->nullable()->constrained('semesters')->nullOnDelete();
        });
        Schema::table('absence_permit_types', function (Blueprint $table) {
            $table->foreignId('semester_id')->nullable()->constrained('semesters')->nullOnDelete();
        });
        Schema::table('absence_permits', function (Blueprint $table) {
            $table->foreignId('semester_id')->nullable()->constrained('semesters')->nullOnDelete();
        });
        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('semester_id')->nullable()->constrained('semesters')->nullOnDelete();
        });
        Schema::table('days', function (Blueprint $table) {
            $table->foreignId('semester_id')->nullable()->constrained('semesters')->nullOnDelete();
        });
        Schema::table('attendance_schedules', function (Blueprint $table) {
            $table->foreignId('semester_id')->nullable()->constrained('semesters')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('check_in_statuses', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
            $table->dropColumn('semester_id');
        });
        Schema::table('check_out_statuses', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
            $table->dropColumn('semester_id');
        });
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
            $table->dropColumn('semester_id');
        });
        Schema::table('attendance_windows', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
            $table->dropColumn('semester_id');
        });
        Schema::table('absence_permit_types', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
            $table->dropColumn('semester_id');
        });
        Schema::table('absence_permits', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
            $table->dropColumn('semester_id');
        });
        Schema::table('days', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
            $table->dropColumn('semester_id');
        });
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
            $table->dropColumn('semester_id');
        });
        Schema::table('attendance_schedules', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
            $table->dropColumn('semester_id');
        });
    }
};
