<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Traits\HasRoleVisibility;

class JobFamily extends Model
{
    use LogsActivity, HasFactory, HasRoleVisibility;

    protected $fillable = [
        'company_id',
        'name',
        'description',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('job_family')
            ->logOnlyDirty();
    }

    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->properties = $activity->properties->merge([
            'company_id' => $this->company_id,
        ]);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function jobRoles()
    {
        return $this->hasMany(JobRole::class);
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'course_job_family')
            ->withPivot(['mandatory'])
            ->withTimestamps();
    }
}
