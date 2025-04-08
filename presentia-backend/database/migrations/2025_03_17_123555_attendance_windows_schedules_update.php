<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
            Schema::table('attendance_windows', function (Blueprint $table) {
                $table->foreignId('event_id')->nullable()->constrained('events')->cascadeOnDelete();
            });

            // Modify the 'type' column in attendance_schedules
            Schema::table('attendance_schedules', function (Blueprint $table) {
                $table->enum('type', ['default', 'event', 'holiday', 'event_holiday'])->change();
                $table->dropColumn('date'); // Remove the 'date' column
            });

            // Modify the 'type' column in attendance_windows
            Schema::table('attendance_windows', function (Blueprint $table) {
                $table->enum('type', ['default', 'event', 'holiday', 'event_holiday'])->change();
            });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
            Schema::table('attendance_windows', function (Blueprint $table) {
                $table->dropForeign(['event_id']);
                $table->dropColumn('event_id');
            });

            // Revert 'type' column changes in attendance_schedules
            Schema::table('attendance_schedules', function (Blueprint $table) {
                $table->enum('type', ['default', 'event', 'holiday'])->change();
                $table->date('date'); // Restore the 'date' column
            });

            // Revert 'type' column changes in attendance_windows
            Schema::table('attendance_windows', function (Blueprint $table) {
                $table->enum('type', ['default', 'event', 'holiday'])->change();
            });
    }
};
