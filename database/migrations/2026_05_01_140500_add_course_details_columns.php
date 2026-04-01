<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('status')->default('active');
            $table->string('category')->nullable();
            $table->string('course_type')->default('online'); // online, in-person
            $table->integer('duration')->nullable(); // duration in minutes
            $table->string('provider')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['status', 'category', 'course_type', 'duration', 'provider']);
        });
    }
};
