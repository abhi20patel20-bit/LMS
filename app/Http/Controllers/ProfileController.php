<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\User\UserProfileCourseService;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request, UserProfileCourseService $service): Response
    {
        $user = $request->user();
        $user->loadMissing(['department', 'company', 'roles']);

        $coursePayload = $service->getCourses($user);

        $teamMembers = collect();
        if ($user->department_id) {
            $teamMembers = User::query()
                ->where('department_id', $user->department_id)
                ->with('jobRole')
                ->orderBy('name')
                ->get(['id', 'name', 'job_role_id', 'department_id']);
        }

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'profile' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'company' => $user->company?->name,
                'department' => $user->department?->name,
                'departmentId' => $user->department_id,
                'jobRole' => $user->jobRole?->name,
                'jobRoleId' => $user->job_role_id,
                'role' => $user->roles->first()?->name,
                'status' => $user->status,
                'joinedAt' => $user->created_at,
                'emailVerifiedAt' => $user->email_verified_at,
            ],
            'mandatoryCourses' => $coursePayload['mandatory'],
            'nonMandatoryCourses' => $coursePayload['optional'],
            'teamMembers' => $teamMembers->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'jobRole' => $member->jobRole?->name,
                ];
            })->values(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
