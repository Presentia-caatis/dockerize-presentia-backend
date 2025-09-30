<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        
        Schema::table('check_in_statuses', function (Blueprint $table) {
            $table->dropUnique(['late_duration', 'school_id']);
            $table->unique(['late_duration', 'school_id', 'semester_id']);
        });

        
        Schema::table('check_out_statuses', function (Blueprint $table) {
            $table->dropUnique(['late_duration', 'school_id']);
            $table->unique(['late_duration', 'school_id', 'semester_id']);
        });

        
        Schema::table('absence_permit_types', function (Blueprint $table) {
            $table->dropUnique(['school_id', 'permit_name']);
            $table->unique(['school_id', 'permit_name', 'semester_id']);
        });

        Schema::table('days', function (Blueprint $table) {
            $table->dropUnique(['name', 'school_id']);
            $table->unique(['school_id', 'name', 'semester_id']);
        });


    }

    public function down(): void
    {
        // Rollback for check_in_statuses
        Schema::table('check_in_statuses', function (Blueprint $table) {
            $table->dropUnique(['late_duration', 'school_id', 'semester_id']);
            $table->unique(['late_duration', 'school_id']);
        });

        // Rollback for check_out_statuses
        Schema::table('check_out_statuses', function (Blueprint $table) {
            $table->dropUnique(['late_duration', 'school_id', 'semester_id']);
            $table->unique(['late_duration', 'school_id']);
        });

        // Rollback for absence_permit_types
        Schema::table('absence_permit_types', function (Blueprint $table) {
            $table->dropUnique(['school_id', 'permit_name', 'semester_id']);
            $table->unique(['school_id', 'permit_name']);
        });

        Schema::table('days', function (Blueprint $table) {
            $table->dropUnique(['school_id', 'name', 'semester_id']);
            $table->unique(['name', 'school_id']);
        });
    }
};
