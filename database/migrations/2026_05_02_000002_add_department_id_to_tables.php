<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
        });

        Schema::table('job_roles', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
        });

        Schema::table('course_categories', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
        });

        Schema::table('job_roles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
        });

        Schema::table('course_categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
        });
    }
};
