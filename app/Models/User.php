<?php

namespace App\Models;

use DateTimeImmutable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Models\Activity;
use App\Models\Traits\HasRoleVisibility;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes, LogsActivity, HasRoleVisibility;

    protected $guard_name = 'web';

    protected $dates = [
        'deleted_at',
        'suspended_until',
    ];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_DELETED = 'deleted';

    protected $fillable = [
        'name',
        'email',
        'password',
        'company_id',
        'suspension_reason',
        'status',
        'department_id',
        'job_role_id'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Activity log configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('user')
            ->logOnlyDirty();
    }

    /**
     * Add custom fields to activity log entries
     */
    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->properties = $activity->properties->merge([
            'company_id' => $this->company_id,
            'department_id' => $this->department_id,
        ]);
    }

    /**
     * Relationships
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function jobRole()
    {
        return $this->belongsTo(JobRole::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function courseBookings()
    {
        return $this->hasMany(CourseBooking::class);
    }

    public function courseWaitlistEntries()
    {
        return $this->hasMany(CourseWaitlist::class);
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'enrollments')
            ->withPivot(['enrollment_type','status','completed_at','source','source_id','due_at','expires_at'])
            ->withTimestamps();
    }

    /**
     * Custom attribute formats
     */
    public function getCreatedAtAttribute()
    {
        return date('d-m-Y H:i', strtotime($this->attributes['created_at']));
    }

    public function getUpdatedAtAttribute()
    {
        return date('d-m-Y H:i', strtotime($this->attributes['updated_at']));
    }

    public function getEmailVerifiedAtAttribute()
    {
        return $this->attributes['email_verified_at'] == null
            ? null
            : date('d-m-Y H:i', strtotime($this->attributes['email_verified_at']));
    }

    /**
     * Permissions array helper
     */
    public function getPermissionArray()
    {
        return $this->getAllPermissions()->mapWithKeys(function ($pr) {
            return [$pr['name'] => true];
        });
    }

    /**
     * Status helpers
     */
    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Suspend user
     */
    public function suspend(?string $reason, DateTimeImmutable $suspensionTill)
    {
        $this->status = self::STATUS_SUSPENDED;
        $this->suspension_reason = $reason;
        $this->suspended_until = $suspensionTill;
        $this->save();
    }

    /**
     * Unsuspend user
     */
    public function unsuspend()
    {
        $this->status = self::STATUS_ACTIVE;
        $this->suspension_reason = null;
        $this->suspended_until = null;
        $this->save();
    }

    /**
     * Soft delete override to update status
     */
    public function delete()
    {
        $this->status = self::STATUS_DELETED;
        $this->save();

        parent::delete();
    }
}
