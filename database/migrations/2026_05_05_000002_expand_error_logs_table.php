<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('error_logs', function (Blueprint $table) {
            $table->dateTime('occurred_at')->useCurrent()->after('id');
            $table->string('environment')->nullable()->after('occurred_at');
            $table->string('app_version')->nullable()->after('environment');
            $table->string('git_sha')->nullable()->after('app_version');
            $table->string('level')->default('error')->after('git_sha');
            $table->string('exception_class')->nullable()->after('level');
            $table->integer('status_code')->nullable()->after('code');
            $table->text('url')->nullable()->after('trace');
            $table->string('method')->nullable()->after('url');
            $table->string('ip')->nullable()->after('method');
            $table->text('user_agent')->nullable()->after('ip');
            $table->string('request_id')->nullable()->after('user_agent');
            $table->json('headers')->nullable()->after('request_id');
            $table->json('payload')->nullable()->after('headers');
            $table->string('session_id')->nullable()->after('payload');
            $table->string('route_name')->nullable()->after('session_id');
            $table->string('component')->nullable()->after('route_name');
        });

        Schema::table('error_logs', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('error_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('error_logs', function (Blueprint $table) {
            $table->dropColumn([
                'occurred_at',
                'environment',
                'app_version',
                'git_sha',
                'level',
                'exception_class',
                'status_code',
                'url',
                'method',
                'ip',
                'user_agent',
                'request_id',
                'headers',
                'payload',
                'session_id',
                'route_name',
                'component',
            ]);
        });
    }
};
