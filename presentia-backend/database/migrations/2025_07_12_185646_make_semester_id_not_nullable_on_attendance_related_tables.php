<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Drop existing foreign keys
        Schema::table('check_in_statuses', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
        });
        Schema::table('check_out_statuses', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
        });
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
        });
        Schema::table('attendance_windows', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
        });
        Schema::table('absence_permit_types', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
        });
        Schema::table('absence_permits', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
        });
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
        });
        Schema::table('days', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
        });
        Schema::table('attendance_schedules', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
        });

        // 2. Change column to NOT NULL
        Schema::table('check_in_statuses', function (Blueprint $table) {
            $table->unsignedBigInteger('semester_id')->nullable(false)->change();
        });
        Schema::table('check_out_statuses', function (Blueprint $table) {
            $table->unsignedBigInteger('semester_id')->nullable(false)->change();
        });
        Schema::table('attendances', function (Blueprint $table) {
            $table->unsignedBigInteger('semester_id')->nullable(false)->change();
        });
        Schema::table('attendance_windows', function (Blueprint $table) {
            $table->unsignedBigInteger('semester_id')->nullable(false)->change();
        });
        Schema::table('absence_permit_types', function (Blueprint $table) {
            $table->unsignedBigInteger('semester_id')->nullable(false)->change();
        });
        Schema::table('absence_permits', function (Blueprint $table) {
            $table->unsignedBigInteger('semester_id')->nullable(false)->change();
        });
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedBigInteger('semester_id')->nullable(false)->change();
        });
        Schema::table('days', function (Blueprint $table) {
            $table->unsignedBigInteger('semester_id')->nullable(false)->change();
        });
        Schema::table('attendance_schedules', function (Blueprint $table) {
            $table->unsignedBigInteger('semester_id')->nullable(false)->change();
        });

        // 3. Add new foreign keys (with CASCADE or RESTRICT)
        Schema::table('check_in_statuses', function (Blueprint $table) {
            $table->foreign('semester_id')->references('id')->on('semesters')->cascadeOnDelete();
        });
        Schema::table('check_out_statuses', function (Blueprint $table) {
            $table->foreign('semester_id')->references('id')->on('semesters')->cascadeOnDelete();
        });
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreign('semester_id')->references('id')->on('semesters')->cascadeOnDelete();
        });
        Schema::table('attendance_windows', function (Blueprint $table) {
            $table->foreign('semester_id')->references('id')->on('semesters')->cascadeOnDelete();
        });
        Schema::table('absence_permit_types', function (Blueprint $table) {
            $table->foreign('semester_id')->references('id')->on('semesters')->cascadeOnDelete();
        });
        Schema::table('absence_permits', function (Blueprint $table) {
            $table->foreign('semester_id')->references('id')->on('semesters')->cascadeOnDelete();
        });
        Schema::table('events', function (Blueprint $table) {
            $table->foreign('semester_id')->references('id')->on('semesters')->cascadeOnDelete();
        });
        Schema::table('days', function (Blueprint $table) {
            $table->foreign('semester_id')->references('id')->on('semesters')->cascadeOnDelete();
        });
        Schema::table('attendance_schedules', function (Blueprint $table) {
            $table->foreign('semester_id')->references('id')->on('semesters')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        // Reverse: drop, change to nullable, re-add nullOnDelete

        Schema::table('check_in_statuses', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
            $table->unsignedBigInteger('semester_id')->nullable()->change();
            $table->foreign('semester_id')->references('id')->on('semesters')->nullOnDelete();
        });
        Schema::table('check_out_statuses', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
            $table->unsignedBigInteger('semester_id')->nullable()->change();
            $table->foreign('semester_id')->references('id')->on('semesters')->nullOnDelete();
        });
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
            $table->unsignedBigInteger('semester_id')->nullable()->change();
            $table->foreign('semester_id')->references('id')->on('semesters')->nullOnDelete();
        });
        Schema::table('attendance_windows', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
            $table->unsignedBigInteger('semester_id')->nullable()->change();
            $table->foreign('semester_id')->references('id')->on('semesters')->nullOnDelete();
        });
        Schema::table('absence_permit_types', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
            $table->unsignedBigInteger('semester_id')->nullable()->change();
            $table->foreign('semester_id')->references('id')->on('semesters')->nullOnDelete();
        });
        Schema::table('absence_permits', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
            $table->unsignedBigInteger('semester_id')->nullable()->change();
            $table->foreign('semester_id')->references('id')->on('semesters')->nullOnDelete();
        });
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
            $table->unsignedBigInteger('semester_id')->nullable()->change();
            $table->foreign('semester_id')->references('id')->on('semesters')->nullOnDelete();
        });
        Schema::table('days', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
            $table->unsignedBigInteger('semester_id')->nullable()->change();
            $table->foreign('semester_id')->references('id')->on('semesters')->nullOnDelete();
        });
        Schema::table('attendance_schedules', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
            $table->unsignedBigInteger('semester_id')->nullable()->change();
            $table->foreign('semester_id')->references('id')->on('semesters')->nullOnDelete();
        });
    }
};
