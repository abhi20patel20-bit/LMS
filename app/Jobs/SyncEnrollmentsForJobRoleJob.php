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

class SyncEnrollmentsForJobRoleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $jobRoleId, public int $actorId)
    {
    }

    public function handle(UserEnrollmentSyncService $syncService): void
    {
        $processed = 0;

        User::query()
            ->where('job_role_id', $this->jobRoleId)
            ->select(['id', 'job_role_id'])
            ->chunkById(250, function ($users) use ($syncService, &$processed) {
                foreach ($users as $user) {
                    $syncService->syncUser($user);
                    $processed++;
                }
            });

        Log::info('Job role enrollment sync complete.', [
            'job_role_id' => $this->jobRoleId,
            'actor_id' => $this->actorId,
            'processed' => $processed,
        ]);
    }
}
