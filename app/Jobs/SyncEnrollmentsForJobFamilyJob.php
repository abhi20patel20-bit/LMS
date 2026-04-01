<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\User\UserEnrollmentSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncEnrollmentsForJobFamilyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $jobFamilyId, public int $actorId)
    {
    }

    public function handle(UserEnrollmentSyncService $syncService): void
    {
        $processed = 0;

        User::query()
            ->whereHas('jobRole', function ($query) {
                $query->where('job_family_id', $this->jobFamilyId);
            })
            ->select(['id', 'job_role_id'])
            ->chunkById(250, function ($users) use ($syncService, &$processed) {
                foreach ($users as $user) {
                    $syncService->syncUser($user);
                    $processed++;
                }
            });

        Log::info('Job family enrollment sync complete.', [
            'job_family_id' => $this->jobFamilyId,
            'actor_id' => $this->actorId,
            'processed' => $processed,
        ]);
    }
}
