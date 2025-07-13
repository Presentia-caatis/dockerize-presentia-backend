<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        
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
    }

    public function down(): void
    {
        Schema::table('check_in_statuses', function (Blueprint $table) {
            $table->unsignedBigInteger('semester_id')->nullable()->change();
        });
        Schema::table('check_out_statuses', function (Blueprint $table) {
            $table->unsignedBigInteger('semester_id')->nullable()->change();
        });
        Schema::table('attendances', function (Blueprint $table) {
            $table->unsignedBigInteger('semester_id')->nullable()->change();
        });
        Schema::table('attendance_windows', function (Blueprint $table) {
            $table->unsignedBigInteger('semester_id')->nullable()->change();
        });
        Schema::table('absence_permit_types', function (Blueprint $table) {
            $table->unsignedBigInteger('semester_id')->nullable()->change();
        });
        Schema::table('absence_permits', function (Blueprint $table) {
            $table->unsignedBigInteger('semester_id')->nullable()->change();
        });
        Schema::table('days', function (Blueprint $table) {
            $table->unsignedBigInteger('semester_id')->nullable()->change();
        });
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedBigInteger('semester_id')->nullable()->change();
        });
        Schema::table('attendance_schedules', function (Blueprint $table) {
            $table->unsignedBigInteger('semester_id')->nullable()->change();
        });
    }
};
