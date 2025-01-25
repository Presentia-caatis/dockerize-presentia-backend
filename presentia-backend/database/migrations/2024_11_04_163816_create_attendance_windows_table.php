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
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('day_id')->constrained('days');
            $table->string('name');
            $table->integer('total_present');
            $table->integer('total_absent');
            $table->date('date');
            $table->enum('type', ['default', 'event' , 'holiday'])->default('event');
            $table->timestamp('check_in_start_time')->nullable();
            $table->timestamp('check_in_end_time')->nullable();
            $table->timestamp('check_out_start_time')->nullable();
            $table->timestamp('check_out_end_time')->nullable();
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
