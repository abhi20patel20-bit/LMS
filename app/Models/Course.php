<?php

namespace App\Models;

use App\Models\Traits\HasRoleVisibility;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use LogsActivity, HasFactory, HasRoleVisibility, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'price',
        'settings',
        'status',
        'course_category_id',
        'course_type',
        'duration',
        'delivery_type',
        'default_capacity',
        'booking_required',
    ];

    protected $casts = [
        'booking_required' => 'boolean',
        'default_capacity' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('course')
            ->logOnlyDirty();
    }

    /**
     * Add custom fields to every activity log entry for this model.
     */
    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->properties = $activity->properties->merge([
            'course_id' => $this->id,
        ]);
    }

    public function category()
    {
        return $this->belongsTo(CourseCategory::class, 'course_category_id');
    }

    public function providers()
    {
        return $this->belongsToMany(Provider::class)->withTimestamps();
    }

    public function jobRoles()
    {
        return $this->belongsToMany(JobRole::class)->withPivot(['mandatory','visibility'])->withTimestamps();
    }

    public function jobFamilies()
    {
        return $this->belongsToMany(JobFamily::class, 'course_job_family')
            ->withPivot(['mandatory'])
            ->withTimestamps();
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function sessions()
    {
        return $this->hasMany(CourseSession::class);
    }

    public function bookings()
    {
        return $this->hasMany(CourseBooking::class);
    }

    public function waitlistEntries()
    {
        return $this->hasMany(CourseWaitlist::class);
    }
}
