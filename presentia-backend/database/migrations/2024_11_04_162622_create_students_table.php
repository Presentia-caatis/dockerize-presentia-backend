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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId("class_group_id")->nullable()->constrained("class_groups");
            $table->foreignId("school_id")->constrained("schools")->cascadeOnDelete();
            $table->boolean("is_active")->default(true);
            $table->string("nis");
            $table->string("nisn")->unique();
            $table->string("student_name");
            $table->enum("gender",['male', 'female'])->default('male');
            $table->unique(['nis', 'school_id']); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
