<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasRoleVisibility;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;

class Department extends Model
{
    use LogsActivity, HasFactory, HasRoleVisibility;

    protected $fillable = [
        'name',
        'slug',
        'custom_domain',
        'subscription_type',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('department')
            ->logOnlyDirty();
    }

    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->properties = $activity->properties->merge([
            'department_id' => $this->id,
        ]);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function jobRoles()
    {
        return $this->hasMany(JobRole::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }
}
