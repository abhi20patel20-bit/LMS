<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            // Skip FK modifications for sqlite test runs
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['company_id']);
        });

        // Update column to NOT NULL and move after id
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->after('id')->nullable(false)->change();
        });

        // Recreate foreign key constraint (adjust onDelete if needed)
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
        });
    }
};
