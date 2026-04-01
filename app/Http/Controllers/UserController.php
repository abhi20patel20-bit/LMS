<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use App\Response\UserResponse;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\SuspensionRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Services\User\UserManagementService;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:create users')->only(['store']);
        $this->middleware('permission:read users')->only(['index', 'getUsersByRule', 'getUser']);
        $this->middleware('permission:update users')->only(['update', 'suspend', 'restore']);
        $this->middleware('permission:delete users')->only(['destroy']);
    }

    /**
     *
     * @return Response
     */
    public function index(): Response
    {
        return Inertia::render('User/Index');
    }

    /**
     *
     * @param UserManagementService $service
     * @return JsonResponse
     */
    public function getUsersByRule(UserManagementService $service): JsonResponse
    {
        try
        {
            $users = $service->getUsersByRule(auth()->user());
            return new JsonResponse(UserResponse::many($users), 200);
        } catch (\Throwable $th) {
            return new JsonResponse(['message' => $th->getMessage()], 500);
        }
    }

    public function store(
        UserStoreRequest $request,
        UserManagementService $service
    ): JsonResponse {
        try {
            $result = $service->storeUser($request->validated(), auth()->user());
            return new JsonResponse($result, 201);

        } catch (\Throwable $th) {
            return new JsonResponse(['message' => $th->getMessage()], 500);
        }
    }

    public function update(
        UserUpdateRequest $request,
        int $id,
        UserManagementService $service
    ): JsonResponse {

        try {
            $user = User::findOrFail($id);
            $result = $service->updateUser($user, $request->validated(), auth()->user());
            return new JsonResponse($result, 201);

        } catch (\Throwable $th) {
            return new JsonResponse(['message' => $th->getMessage()], 500);
        }
    }

    /**
     *
     * @param User $user
     * @param UserManagementService $service
     * @return JsonResponse
     */
    public function destroy(User $user, UserManagementService $service): JsonResponse
    {
        try {
            if ($user->hasRole('super-admin')) {
                return new JsonResponse(['message' => 'Super admin cannot be deleted.'], 403);
            }

            $result = $service->deleteUser($user);
            return new JsonResponse($result, 201);

        } catch (\Throwable $th) {
            return new JsonResponse(['message' => $th->getMessage()], 500);
        }
    }

    /**
     *
     * @param integer $id
     * @return JsonResponse
     */
    public function getUser(int $id): JsonResponse
    {
        try {
            $user = User::with('roles')->findOrFail($id);
            return new JsonResponse($user, 200);

        } catch (\Throwable $th) {
            return new JsonResponse(['message' => $th->getMessage()], 500);
        }
    }

    /**
     *
     * @param SuspensionRequest $request
     * @param UserManagementService $service
     * @return JsonResponse
     */
    public function suspend(
        SuspensionRequest $request,
        UserManagementService $service
    ): JsonResponse {
        try {
            $user = User::findOrFail($request->user['id']);
            if ($user->hasRole('super-admin')) {
                return new JsonResponse(['message' => 'Super admin cannot be suspended.'], 403);
            }
            $result = $service->suspendUser($user, $request->reason, $request->until, auth()->user());
            return new JsonResponse($result, 201);

        } catch (\Throwable $th) {
            return new JsonResponse(['message' => $th->getMessage()], 500);
        }
    }

    /**
     *
     * @param integer $id
     * @param UserManagementService $service
     * @return JsonResponse
     */
    public function restore(int $id, UserManagementService $service): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            $result = $service->restoreUser($user);
            return new JsonResponse($result, 201);

        } catch (\Throwable $th) {
            return new JsonResponse(['message' => $th->getMessage()], 500);
        }
    }
}
