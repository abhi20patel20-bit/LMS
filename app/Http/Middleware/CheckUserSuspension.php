<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CheckUserSuspension
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->status === 'suspended') {
            // Logout if suspended
            Auth::logout();

            // Throw 403 instead of returning JSON
            throw new HttpException(403, 'Your account has been suspended. Please contact admin to restore.');
        }


        return $next($request);
    }
}
