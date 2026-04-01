<?php

// database/migrations/2025_12_09_000005_create_course_job_role_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_job_role', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_role_id')->constrained()->cascadeOnDelete();
            $table->boolean('mandatory')->default(false);
            $table->string('visibility')->default('visible'); // visible, hidden, require_enroll
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_job_role');
    }
};

