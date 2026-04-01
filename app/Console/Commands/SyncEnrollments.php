<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\User\UserEnrollmentSyncService;
use Illuminate\Console\Command;

class SyncEnrollments extends Command
{
    protected $signature = 'lms:sync-enrollments {--user_id=}';

    protected $description = 'Sync mandatory enrollments for one user or all users';

    public function handle(UserEnrollmentSyncService $syncService): int
    {
        $userId = $this->option('user_id');

        if ($userId) {
            $user = User::find($userId);

            if (!$user) {
                $this->error("User {$userId} not found.");
                return self::FAILURE;
            }

            $syncService->syncUser($user);
            $this->info("Enrollments synced for user {$userId}.");

            return self::SUCCESS;
        }

        $this->info('Syncing enrollments for all users...');

        User::query()->chunkById(200, function ($users) use ($syncService) {
            foreach ($users as $user) {
                $syncService->syncUser($user);
            }
        });

        $this->info('Enrollment sync complete.');

        return self::SUCCESS;
    }
}
