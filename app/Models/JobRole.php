<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Traits\HasRoleVisibility;

class JobRole extends Model
{
    use LogsActivity, HasFactory, HasRoleVisibility;

    protected $fillable = [
        'job_family_id',
        'name',
        'description'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('job_role')
            ->logOnlyDirty();
    }

    /**
     * Add custom fields to every activity log entry for this model.
     */
    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->properties = $activity->properties->merge([
            'job_family_id' => $this->job_family_id,
        ]);
    }

    public function jobFamily()
    {
        return $this->belongsTo(JobFamily::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class)->withPivot(['mandatory','visibility'])->withTimestamps();
    }
}
