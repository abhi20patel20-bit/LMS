<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::dropIfExists('schemes');
            return;
        }

        if (Schema::hasColumn('users', 'scheme_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('scheme_id');
            });
        }

        if (Schema::hasColumn('companies', 'scheme_id')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropConstrainedForeignId('scheme_id');
            });
        }

        if (Schema::hasColumn('job_roles', 'scheme_id')) {
            Schema::table('job_roles', function (Blueprint $table) {
                $table->dropConstrainedForeignId('scheme_id');
            });
        }

        if (Schema::hasColumn('courses', 'scheme_id')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->dropConstrainedForeignId('scheme_id');
            });
        }

        if (Schema::hasColumn('providers', 'scheme_id')) {
            Schema::table('providers', function (Blueprint $table) {
                $table->dropConstrainedForeignId('scheme_id');
            });
        }

        if (Schema::hasColumn('course_categories', 'scheme_id')) {
            Schema::table('course_categories', function (Blueprint $table) {
                $table->dropConstrainedForeignId('scheme_id');
            });
        }

        Schema::dropIfExists('schemes');
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        if (!Schema::hasTable('schemes')) {
            Schema::create('schemes', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('custom_domain')->nullable();
                $table->string('subscription_type')->default('free');
                $table->json('settings')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasColumn('companies', 'scheme_id')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->foreignId('scheme_id')->nullable()->constrained()->cascadeOnDelete();
            });
        }

        if (!Schema::hasColumn('users', 'scheme_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('scheme_id')->nullable()->constrained()->cascadeOnDelete();
            });
        }

        if (!Schema::hasColumn('job_roles', 'scheme_id')) {
            Schema::table('job_roles', function (Blueprint $table) {
                $table->foreignId('scheme_id')->constrained()->cascadeOnDelete();
            });
        }

        if (!Schema::hasColumn('courses', 'scheme_id')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->foreignId('scheme_id')->constrained()->cascadeOnDelete();
            });
        }

        if (!Schema::hasColumn('providers', 'scheme_id')) {
            Schema::table('providers', function (Blueprint $table) {
                $table->foreignId('scheme_id')->constrained()->cascadeOnDelete();
            });
        }

        if (!Schema::hasColumn('course_categories', 'scheme_id')) {
            Schema::table('course_categories', function (Blueprint $table) {
                $table->foreignId('scheme_id')->constrained()->cascadeOnDelete();
            });
        }
    }
};
