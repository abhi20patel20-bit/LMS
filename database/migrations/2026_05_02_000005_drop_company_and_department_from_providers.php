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

        if (Schema::hasColumn('providers', 'department_id')) {
            Schema::table('providers', function (Blueprint $table) {
                $table->dropConstrainedForeignId('department_id');
            });
        }

        if (Schema::hasColumn('providers', 'company_id')) {
            Schema::table('providers', function (Blueprint $table) {
                $table->dropConstrainedForeignId('company_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        if (!Schema::hasColumn('providers', 'company_id')) {
            Schema::table('providers', function (Blueprint $table) {
                $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('providers', 'department_id')) {
            Schema::table('providers', function (Blueprint $table) {
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            });
        }
    }
};
