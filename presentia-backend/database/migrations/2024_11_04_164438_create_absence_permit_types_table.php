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
        Schema::create('absence_permit_types', function (Blueprint $table) {
            $table->id();
            $table->string("permit_name");
            $table->boolean("is_active")->default(true);
            $table->foreignId("school_id")->constrained("schools")->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained('semesters')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['school_id', 'permit_name', 'semester_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absence_permit_types');
    }
};
