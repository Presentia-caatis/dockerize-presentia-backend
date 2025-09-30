<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Remove FK first if exists
            $table->dropForeign(['class_group_id']);
            $table->dropColumn('class_group_id');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->unsignedBigInteger('class_group_id')->nullable();
            $table->foreign('class_group_id')->references('id')->on('class_groups')->nullOnDelete();
        });
    }
};
