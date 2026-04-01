<?php

namespace App\Response;

use App\Models\User;

class UserResponse
{
    /**
     * Format a single user
     *
     * @param User $user
     * @return array
     */
    public static function one(User $user): array
    {
        return [
            'id'      => $user->id,
            'name'    => $user->name,
            'email'   => $user->email,
            'company' => $user->company?->name ?? null,
            'role'   => $user->roles->first()?->name ?? null,
            'role_id'   => $user->roles->first()?->id ?? null,
            'company_id'   => $user->company_id ?? null,
            'department'   => $user->department?->name ?? null,
            'department_id'   => $user->department_id ?? null,
            'job_role'   => $user->jobRole?->name ?? null,
            'job_role_id'   => $user->job_role_id ?? null,
            'status' => $user->status,
            'reason' => $user->suspension_reason
        ];
    }

    /**
     * Format a collection of users
     *
     * @param iterable $users
     * @return array
     */
    public static function many($users): array
    {
        return collect($users)->map(fn($user) => self::one($user))->toArray();
    }
}
