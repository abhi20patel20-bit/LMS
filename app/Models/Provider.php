<?php

namespace App\Models;

use App\Models\Traits\HasRoleVisibility;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Provider extends Model
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
            ->useLogName('provider')
            ->logOnlyDirty();
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class)->withTimestamps();
    }

    public function sessions()
    {
        return $this->hasMany(CourseSession::class);
    }
}
