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
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->foreignId("subscription_plan_id")->constrained("subscription_plans");
            $table->string('logo_image_path')->nullable();
            $table->string("name");
            $table->string("address");
            $table->timestamp("latest_subscription");
            $table->boolean("is_task_scheduling_active")->default(false);
            $table->string('timezone')->default('UTC');
            $table->string('school_token')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
