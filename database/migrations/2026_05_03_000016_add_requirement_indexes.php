<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->index(['user_id', 'enrollment_type']);
        });

        Schema::table('course_job_family', function (Blueprint $table) {
            $table->index(['job_family_id', 'course_id']);
        });

        Schema::table('course_job_role', function (Blueprint $table) {
            $table->index(['job_role_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'enrollment_type']);
        });

        Schema::table('course_job_family', function (Blueprint $table) {
            $table->dropIndex(['job_family_id', 'course_id']);
        });

        Schema::table('course_job_role', function (Blueprint $table) {
            $table->dropIndex(['job_role_id', 'course_id']);
        });
    }
};
