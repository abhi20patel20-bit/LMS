<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_session_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['booked', 'cancelled', 'attended', 'no_show'])->default('booked');
            $table->dateTime('booked_at');
            $table->dateTime('cancelled_at')->nullable();
            $table->dateTime('attended_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'course_session_id']);
            $table->index(['course_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_bookings');
    }
};
