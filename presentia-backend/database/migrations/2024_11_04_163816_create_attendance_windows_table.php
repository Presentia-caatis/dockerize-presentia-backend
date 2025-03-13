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
            $table->enum('type', ['default', 'event' , 'holiday'])->default('event');
            $table->time('check_in_start_time')->nullable();
            $table->time('check_in_end_time')->nullable();
            $table->time('check_out_start_time')->nullable();
            $table->time('check_out_end_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_windows');
    }
};
