<?php

namespace App\Models;

use App\Models\Traits\HasRoleVisibility;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CourseCategory extends Model
{
    use LogsActivity, HasFactory, HasRoleVisibility;

    protected $fillable = [
        'name',
        'description',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('course_category')
            ->logOnlyDirty();
    }

    /**
     * Add custom fields to every activity log entry for this model.
     */
    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->properties = $activity->properties->merge([
            'course_category_id' => $this->id,
        ]);
    }

    public function courses()
    {
        return $this->hasMany(Course::class, 'course_category_id');
    }
}
