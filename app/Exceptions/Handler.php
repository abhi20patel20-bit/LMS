<?php

namespace App\Exceptions;

use App\Services\Logging\ErrorLoggingService;
use Throwable;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     *
     * @param Throwable $exception
     * @return void
     */
    public function report(Throwable $exception)
    {
        $request = app()->bound('request') ? app('request') : null;
        app(ErrorLoggingService::class)->log($exception, $request);

        parent::report($exception);
    }
}
