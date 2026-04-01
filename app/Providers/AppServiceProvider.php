<?php

namespace App\Providers;

use App\Services\Logging\ErrorLoggingService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        register_shutdown_function(function (): void {
            $error = error_get_last();
            if ($error === null) {
                return;
            }

            $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
            if (!in_array($error['type'] ?? null, $fatalTypes, true)) {
                return;
            }

            $request = app()->bound('request') ? app('request') : null;
            app(ErrorLoggingService::class)->logFatal($error, $request);
        });
    }
}
