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
        Schema::create('attendance_windows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->nullable()->constrained('events')->cascadeOnDelete();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('day_id')->constrained('days');
            $table->string('name');
            $table->date('date');
            $table->enum('type', ['default', 'event' , 'holiday', 'event_holiday'])->default('event');
            $table->time('check_in_start_time')->nullable();
            $table->time('check_in_end_time')->nullable();
            $table->time('check_out_start_time')->nullable();
            $table->time('check_out_end_time')->nullable();
            $table->timestamps();
        });

        // Add the INSERT Trigger
        DB::unprepared("
            CREATE TRIGGER check_overlap_insert
            BEFORE INSERT ON attendance_windows
            FOR EACH ROW
            BEGIN
                IF EXISTS (
                    SELECT 1 FROM attendance_windows
                    WHERE date = NEW.date
                    AND id != NEW.id  -- Ignore itself
                    AND (
                        (NEW.check_in_start_time < check_in_end_time AND NEW.check_in_end_time > check_in_start_time)
                        OR (NEW.check_out_start_time < check_out_end_time AND NEW.check_out_end_time > check_out_start_time)
                    )
                ) THEN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'Overlap detected with an existing attendance window';
                END IF;
            END
        ");

        // Add the UPDATE Trigger
        DB::unprepared("
            CREATE TRIGGER check_overlap_update
            BEFORE UPDATE ON attendance_windows
            FOR EACH ROW
            BEGIN
                IF EXISTS (
                    SELECT 1 FROM attendance_windows
                    WHERE date = NEW.date
                    AND id != OLD.id  -- Ignore the current record being updated
                    AND (
                        (NEW.check_in_start_time < check_in_end_time AND NEW.check_in_end_time > check_in_start_time)
                        OR (NEW.check_out_start_time < check_out_end_time AND NEW.check_out_end_time > check_out_start_time)
                    )
                ) THEN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'Overlap detected with an existing attendance window';
                END IF;
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP TRIGGER IF EXISTS check_overlap_insert");
        DB::unprepared("DROP TRIGGER IF EXISTS check_overlap_update");

        Schema::dropIfExists('attendance_windows');
    }
};
