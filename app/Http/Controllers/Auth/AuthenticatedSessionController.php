<?php

namespace App\Http\Controllers\Auth;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use App\Models\Role;
use App\Models\Permission;
use App\Providers\RouteServiceProvider;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        $request->session()->regenerate();

        $user = Auth::user()->load('company', 'roles', 'department');
        $this->ensureEmployeeAccess($user);
        $defaultRedirect = ($user->can('read user dashboard') || $user->can('read my learning'))
            ? '/lms/dashboard'
            : route('dashboard');
        $intended = session()->pull('url.intended');
        $redirect = $this->resolveRedirect($user, $intended, $defaultRedirect);

        return response()->json([
            'message' => 'Login successful.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'department' => $user->department,
                'company' => $user->company,
                'roles' => $user->roles->pluck('name'),
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ],
            'redirect' => $redirect,
        ], 201);
    }

    /**
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function ensureEmployeeAccess($user): void
    {
        if (!$user || !$user->roles->contains('name', 'employee')) {
            return;
        }

        if (
            $user->can('read user dashboard') &&
            $user->can('read my learning') &&
            $user->can('read metrics')
        ) {
            return;
        }

        $employeeRole = Role::query()->where('name', 'employee')->first();
        if (!$employeeRole) {
            return;
        }

        if ($employeeRole->guard_name !== 'web') {
            $employeeRole->guard_name = 'web';
            $employeeRole->save();
        }

        $permissions = [
            'read user dashboard',
            'read my learning',
            'read metrics',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }

        $employeeRole->syncPermissions($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->load('roles');
    }

    private function resolveRedirect($user, ?string $intended, string $defaultRedirect): string
    {
        if (!$intended) {
            return $defaultRedirect;
        }

        $path = parse_url($intended, PHP_URL_PATH) ?? $intended;

        $permissionMap = [
            '/lms/dashboard' => 'read user dashboard',
            '/lms/learning' => 'read my learning',
            '/lms/my-place' => 'read my learning',
            '/lms/metrics' => 'read metrics',
            '/matrices' => 'read metrics',
            '/dashboard' => 'read users',
            '/user' => 'read users',
            '/role' => 'read roles',
            '/permission' => 'read permissions',
        ];

        foreach ($permissionMap as $prefix => $permission) {
            if (str_starts_with($path, $prefix)) {
                return $user->can($permission) ? $intended : $defaultRedirect;
            }
        }

        if (str_starts_with($path, '/lms')) {
            return ($user->can('read user dashboard') || $user->can('read my learning') || $user->can('read metrics'))
                ? $intended
                : $defaultRedirect;
        }

        return $defaultRedirect;
    }
}
