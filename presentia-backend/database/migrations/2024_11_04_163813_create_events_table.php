<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_scheduler_active')->default(true);
            
            // EVENT DURATION ; (occurancences = 0 && start_date) => one time event 
            $table->integer('occurrences')->nullable(); //n : end after n occurrences
            $table->date('start_date');
            $table->date('end_date')->nullable();

            // RECURRING EVENT
            $table->enum('recurring_frequency', ['daily', 'daily_exclude_holiday','weekly', 'monthly', 'yearly', 'one_time'])->default('one_time');
            $table->json('days_of_month')->nullable();  // Specific day (1st, 2nd, etc.) negative value is for the date that is counted from the end of the month
            $table->json('days_of_week')->nullable();  // Store multiple days as JSON ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']
            $table->integer('interval')->default(1); // Repeat every X requrrings
            $table->json('weeks_of_month')->nullable(); // Repeat every Yst,nd,rd days of week
            $table->json('yearly_dates')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('events');
        Schema::enableForeignKeyConstraints();
    }
};
