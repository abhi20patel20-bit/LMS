<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CourseWaitlist extends Model
{
    use HasFactory;

    public const STATUS_WAITING = 'waiting';
    public const STATUS_PROMOTED = 'promoted';
    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'course_waitlist';

    protected $fillable = [
        'user_id',
        'course_id',
        'course_session_id',
        'position',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function session()
    {
        return $this->belongsTo(CourseSession::class, 'course_session_id');
    }
}
