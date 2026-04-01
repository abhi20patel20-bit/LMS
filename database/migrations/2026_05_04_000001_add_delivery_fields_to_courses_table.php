<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->enum('delivery_type', ['self_paced', 'scheduled'])->default('self_paced');
            $table->integer('default_capacity')->nullable();
            $table->boolean('booking_required')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['delivery_type', 'default_capacity', 'booking_required']);
        });
    }
};
