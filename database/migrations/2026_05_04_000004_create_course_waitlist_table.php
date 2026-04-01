<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_waitlist', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_session_id')->constrained()->cascadeOnDelete();
            $table->integer('position')->nullable();
            $table->enum('status', ['waiting', 'promoted', 'cancelled'])->default('waiting');
            $table->timestamps();

            $table->unique(['user_id', 'course_session_id']);
            $table->index(['course_session_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_waitlist');
    }
};
