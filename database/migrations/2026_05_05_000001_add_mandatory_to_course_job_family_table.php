<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_job_family', function (Blueprint $table) {
            $table->boolean('mandatory')->default(true)->after('job_family_id');
        });
    }

    public function down(): void
    {
        Schema::table('course_job_family', function (Blueprint $table) {
            $table->dropColumn('mandatory');
        });
    }
};
