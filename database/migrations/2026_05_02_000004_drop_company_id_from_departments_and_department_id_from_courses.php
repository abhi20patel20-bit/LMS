<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            // Skip FK drops for sqlite test runs.
            return;
        }

        if (Schema::hasColumn('departments', 'company_id')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->dropConstrainedForeignId('company_id');
            });
        }

        if (Schema::hasColumn('courses', 'department_id')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->dropConstrainedForeignId('department_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        if (!Schema::hasColumn('departments', 'company_id')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            });
        }

        if (!Schema::hasColumn('courses', 'department_id')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            });
        }
    }
};
