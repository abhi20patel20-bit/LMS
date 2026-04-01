<?php

namespace App\Services\User;

use App\Models\Role;
use App\Models\User;
use App\Response\UserResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use DateTimeImmutable;
use Spatie\Permission\PermissionRegistrar;

class UserManagementService
{
    public function __construct(private UserEnrollmentSyncService $enrollmentSyncService)
    {
    }

    /**
     * Get users visible to the authenticated user based on Spatie roles.
     */
    public function getUsersByRule(User $authUser)
    {
        return User::visibleTo($authUser)
            ->with([
                'company:id,name',
                'department:id,name',
                'jobRole:id,name',
                'roles:id,name',
            ])
            ->get(['id', 'name', 'email', 'company_id', 'department_id', 'job_role_id', 'status', 'suspension_reason']);
    }

    /**
     * Store user
     */
    public function storeUser(array $data, User $authUser): array
    {
        return DB::transaction(function () use ($data, $authUser) {

            $departmentId = $data['department_id'] ?? $authUser->department_id;

            $user = User::create([
                'name'       => $data['name'],
                'email'      => $data['email'],
                'password'   => Hash::make($data['password']),
                'company_id' => $data['company_id'],
                'department_id'  => $departmentId,
                'job_role_id' => $data['job_role_id'] ?? null,
            ]);

            $roleName = $this->resolveRoleName($data);
            if ($roleName) {
                $user->syncRoles([$roleName]);
                app(PermissionRegistrar::class)->forgetCachedPermissions();
                $user->load('roles');
            }

            $this->enrollmentSyncService->syncUser($user);

            return [
                'message' => 'User created successfully.',
                'users'   => UserResponse::many($this->getUsersByRule($authUser)),
            ];
        });
    }

    /**
     * Update user
     */
    public function updateUser(User $user, array $data, User $authUser): array
    {
        return DB::transaction(function () use ($user, $data, $authUser) {

            $updateData = [];

            if (!empty($data['name']) && $data['name'] !== $user->name) {
                $updateData['name'] = $data['name'];
            }

            if (!empty($data['email']) && $data['email'] !== $user->email) {
                $updateData['email'] = $data['email'];
            }

            if (!empty($data['company_id']) && $data['company_id'] !== $user->company_id) {
                $updateData['company_id'] = $data['company_id'];
            }

            $jobRoleChanged = array_key_exists('job_role_id', $data) && $data['job_role_id'] !== $user->job_role_id;

            if ($jobRoleChanged) {
                $updateData['job_role_id'] = $data['job_role_id'];
            }

            if (!empty($updateData)) {
                $user->update($updateData);
            }

            $roleName = $this->resolveRoleName($data);
            if ($roleName) {
                $user->syncRoles([$roleName]);
                app(PermissionRegistrar::class)->forgetCachedPermissions();
                $user->load('roles');
            }

            if ($jobRoleChanged) {
                $this->enrollmentSyncService->syncUser($user);
            }

            return [
                'message' => 'User updated successfully.',
                'users'   => UserResponse::many($this->getUsersByRule($authUser)),
            ];
        });
    }

    private function resolveRoleName(array $data): ?string
    {
        $roleValue = $data['role'] ?? $data['role_id'] ?? null;
        if (!$roleValue) {
            return null;
        }

        $role = is_numeric($roleValue)
            ? Role::query()->whereKey((int) $roleValue)->first()
            : Role::query()->where('name', $roleValue)->first();

        if (!$role) {
            return null;
        }

        if ($role->guard_name !== 'web') {
            $role->guard_name = 'web';
            $role->save();
        }

        return $role->name;
    }

    /**
     * Suspend user
     */
    public function suspendUser(User $user, string $reason, string $until, User $authUser): array
    {
        if ($user->hasRole('super-admin')) {
            throw new \RuntimeException('Super admin cannot be suspended.');
        }

        return DB::transaction(function () use ($user, $reason, $until, $authUser) {

            $user->suspend($reason, new DateTimeImmutable($until));

            return [
                'message' => 'User suspended successfully.',
                'users'   => UserResponse::many($this->getUsersByRule($authUser)),
            ];
        });
    }

    /**
     * Restore user
     */
    public function restoreUser(User $user): array
    {
        return DB::transaction(function () use ($user) {
            $user->unsuspend();

            return [
                'message' => 'User activated successfully.',
            ];
        });
    }

    /**
     * Delete user
     */
    public function deleteUser(User $user): array
    {
        if ($user->hasRole('super-admin')) {
            throw new \RuntimeException('Super admin cannot be deleted.');
        }

        $user->delete();

        return ['message' => 'User deleted successfully.'];
    }
}
