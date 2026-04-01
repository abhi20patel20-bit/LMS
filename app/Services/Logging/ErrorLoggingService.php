<?php

namespace App\Services\Logging;

use App\Models\ErrorLog;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ErrorLoggingService
{
    private const TRACE_LIMIT = 60000;
    private const MESSAGE_LIMIT = 2000;
    private const DEDUPE_WINDOW_SECONDS = 300;

    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'token',
        'authorization',
        'cookie',
        'x-xsrf-token',
        'xsrf-token',
        'remember_token',
        '_token',
    ];

    public function log(Throwable $exception, ?Request $request = null): void
    {
        try {
            $statusCode = $this->resolveStatusCode($exception);
            $level = $this->resolveLevel($exception, $statusCode);

            $payload = $request ? $this->sanitizePayload($request->all()) : [];
            if ($exception instanceof ValidationException) {
                $payload['validation_errors'] = $this->sanitizeArray($exception->errors());
            }

            if ($payload === []) {
                $payload = null;
            }
            $headers = $request ? $this->sanitizeHeaders($request->headers->all()) : null;

            $data = [
                'occurred_at' => now(),
                'environment' => app()->environment(),
                'app_version' => config('app.version'),
                'git_sha' => config('app.git_sha'),
                'level' => $level,
                'exception_class' => get_class($exception),
                'message' => $this->limitString($exception->getMessage(), self::MESSAGE_LIMIT),
                'code' => $exception->getCode() !== 0 ? (string) $exception->getCode() : null,
                'status_code' => $statusCode,
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $this->limitString($exception->getTraceAsString(), self::TRACE_LIMIT),
                'url' => $request?->fullUrl(),
                'method' => $request?->method(),
                'ip' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'user_id' => $request?->user()?->id,
                'request_id' => $request?->attributes->get('request_id'),
                'headers' => $headers,
                'payload' => $payload,
                'session_id' => $request?->hasSession() ? $request->session()->getId() : null,
                'route_name' => $request?->route()?->getName(),
                'component' => $request?->header('X-Inertia-Component')
                    ?? $request?->attributes->get('inertia_component'),
            ];

            if ($this->isDuplicate($data)) {
                return;
            }

            ErrorLog::query()->create($data);
        } catch (Throwable $loggingException) {
            Log::error('Failed to log exception to database.', [
                'original_exception' => get_class($exception),
                'logging_exception' => $loggingException->getMessage(),
            ]);
        }
    }

    public function logFatal(array $error, ?Request $request = null): void
    {
        $exception = new \ErrorException(
            $error['message'] ?? 'Fatal error',
            0,
            $error['type'] ?? E_ERROR,
            $error['file'] ?? 'unknown',
            $error['line'] ?? 0
        );

        $this->log($exception, $request);
    }

    private function resolveStatusCode(Throwable $exception): ?int
    {
        if ($exception instanceof ValidationException) {
            return 422;
        }

        if ($exception instanceof AuthorizationException) {
            return 403;
        }

        if ($exception instanceof TokenMismatchException) {
            return 419;
        }

        if ($exception instanceof NotFoundHttpException) {
            return 404;
        }

        if ($exception instanceof ModelNotFoundException) {
            return 404;
        }

        if ($exception instanceof HttpExceptionInterface) {
            return $exception->getStatusCode();
        }

        if ($exception instanceof QueryException) {
            return 500;
        }

        return null;
    }

    private function resolveLevel(Throwable $exception, ?int $statusCode): string
    {
        if ($exception instanceof ValidationException || ($statusCode !== null && $statusCode < 500)) {
            return 'warning';
        }

        if ($statusCode !== null && $statusCode >= 500) {
            return 'critical';
        }

        return 'error';
    }

    private function limitString(?string $value, int $limit): ?string
    {
        if ($value === null) {
            return null;
        }

        return Str::limit($value, $limit, '...');
    }

    private function sanitizePayload(array $payload): array
    {
        return $this->sanitizeArray($payload);
    }

    private function sanitizeHeaders(array $headers): array
    {
        return $this->sanitizeArray($headers);
    }

    private function sanitizeArray(array $data): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            $normalizedKey = is_string($key) ? strtolower($key) : $key;
            if (is_string($normalizedKey) && in_array($normalizedKey, self::SENSITIVE_KEYS, true)) {
                $sanitized[$key] = '[redacted]';
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value);
                continue;
            }

            if (is_object($value)) {
                $sanitized[$key] = method_exists($value, '__toString')
                    ? (string) $value
                    : get_class($value);
                continue;
            }

            if (is_resource($value)) {
                $sanitized[$key] = '[resource]';
                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }

    private function isDuplicate(array $data): bool
    {
        $windowStart = now()->subSeconds(self::DEDUPE_WINDOW_SECONDS);

        return ErrorLog::query()
            ->where('exception_class', $data['exception_class'])
            ->where('message', $data['message'])
            ->where('url', $data['url'])
            ->where('status_code', $data['status_code'])
            ->where('level', $data['level'])
            ->where('created_at', '>=', $windowStart)
            ->exists();
    }
}
