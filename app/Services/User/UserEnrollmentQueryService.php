<?php

namespace App\Services\User;

use App\Models\Enrollment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class UserEnrollmentQueryService
{
    public function required(User $user): Builder
    {
        return Enrollment::query()
            ->where('user_id', $user->id)
            ->where('enrollment_type', Enrollment::TYPE_MANDATORY)
            ->where('status', '!=', Enrollment::STATUS_COMPLETED);
    }

    public function inProgress(User $user): Builder
    {
        return Enrollment::query()
            ->where('user_id', $user->id)
            ->where('status', Enrollment::STATUS_IN_PROGRESS);
    }

    public function completed(User $user): Builder
    {
        return Enrollment::query()
            ->where('user_id', $user->id)
            ->where('status', Enrollment::STATUS_COMPLETED);
    }

    public function overdue(User $user, ?Carbon $now = null): Builder
    {
        $now = $now ?? now();

        return Enrollment::query()
            ->where('user_id', $user->id)
            ->whereNotNull('due_at')
            ->where('due_at', '<', $now)
            ->where('status', '!=', Enrollment::STATUS_COMPLETED);
    }

    public function dueSoon(User $user, ?Carbon $now = null, ?Carbon $end = null): Builder
    {
        $now = $now ?? now();
        $end = $end ?? $now->copy()->addDays(30);

        return Enrollment::query()
            ->where('user_id', $user->id)
            ->whereNotNull('due_at')
            ->whereBetween('due_at', [$now, $end])
            ->where('status', '!=', Enrollment::STATUS_COMPLETED);
    }
}
