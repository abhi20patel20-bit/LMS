<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class DevReset extends Command
{
    protected $signature = 'dev:reset';

    protected $description = 'Reset the database and seed a minimal dev baseline';

    public function handle(): int
    {
        if (!app()->environment(['local', 'testing'])) {
            $this->error('dev:reset can only run in local or testing environments.');
            return self::FAILURE;
        }

        $this->info('Resetting database and seeding dev baseline...');

        Artisan::call('migrate:fresh', [
            '--seed' => true,
            '--seeder' => 'Database\\Seeders\\DevBaselineSeeder',
            '--force' => true,
        ]);

        $this->output->write(Artisan::output());

        return self::SUCCESS;
    }
}
