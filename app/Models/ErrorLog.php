<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErrorLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'occurred_at',
        'environment',
        'app_version',
        'git_sha',
        'level',
        'exception_class',
        'code',
        'status_code',
        'message',
        'file',
        'line',
        'trace',
        'url',
        'method',
        'ip',
        'user_agent',
        'user_id',
        'request_id',
        'headers',
        'payload',
        'session_id',
        'route_name',
        'component',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'headers' => 'array',
        'payload' => 'array',
    ];

}
