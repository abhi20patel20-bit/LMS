<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Enrollment extends Model
{
    use LogsActivity, HasFactory;

    public const TYPE_MANDATORY = 'mandatory';
    public const TYPE_OPTIONAL = 'optional';

    public const STATUS_NOT_STARTED = 'not_started';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_EXPIRED = 'expired';

    public const SOURCE_JOB_FAMILY = 'job_family';
    public const SOURCE_JOB_ROLE = 'job_role';
    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_IMPORT = 'import';
    public const SOURCE_REQUIREMENTS = 'requirements';

    protected $fillable = [
        'user_id',
        'course_id',
        'enrollment_type',
        'status',
        'completed_at',
        'source',
        'source_id',
        'due_at',
        'expires_at',
        'cancelled_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'source_id' => 'integer',
        'due_at' => 'datetime',
        'expires_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('enrollment')
            ->logOnlyDirty();
    }

    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->properties = $activity->properties->merge([
            'company_id' => $this->user?->company_id,
            'department_id' => $this->user?->department_id,
        ]);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
