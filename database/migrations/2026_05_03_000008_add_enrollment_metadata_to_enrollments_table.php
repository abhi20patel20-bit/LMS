<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->string('source')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->index(['user_id', 'status']);
            $table->index('due_at');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['due_at']);
            $table->dropIndex(['expires_at']);
            $table->dropColumn(['source', 'source_id', 'due_at', 'expires_at']);
        });
    }
};
