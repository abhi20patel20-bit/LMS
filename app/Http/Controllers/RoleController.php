<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Permission;
use Inertia\Inertia;
use App\Models\Company;
use App\Response\RoleResponse;
use Illuminate\Http\JsonResponse;
use App\Services\Role\RoleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\RoleIndexRequest;
use App\Http\Requests\RoleStoreRequest;
use App\Response\RolesDropdownResponse;
use App\Http\Requests\RoleUpdateRequest;
use Inertia\Response as InertiaResponse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    public function __construct()
    {

        $this->middleware('permission:create roles', ['only' => ['create', 'store']]);
        $this->middleware('permission:read roles', ['only' => ['index', 'show']]);
        $this->middleware('permission:update roles', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete roles', ['only' => ['destroy', 'destroyBulk']]);
    }

    /**
     *
     * @param RoleIndexRequest $request
     * @return InertiaResponse
     */
    public function index(RoleIndexRequest $request): InertiaResponse
    {
        return Inertia::render('Role/Index');
    }

    /**
     *
     * @param RoleStoreRequest $request
     * @return JsonResponse
     */
    public function store(RoleStoreRequest $request): JsonResponse
    {
        $roleData = $request->validated();
        $permissionNames = $this->normalizePermissionNames($roleData['permissions'] ?? []);

        if ($errorResponse = $this->validatePermissionNames($permissionNames)) {
            return $errorResponse;
        }

        // dd($request->validated());
        DB::beginTransaction();
        try {
            $role = Role::create([
                'name'          => $roleData['name'],
                'guard_name'    => 'web',
            ]);
            $role->syncPermissions($permissionNames);
            $role->load('permissions');
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            DB::commit();
            return new JsonResponse([
                'message' => 'Role Added Sucessfully',
                'role' => RoleResponse::one($role),
            ], 201);
        } catch (\Throwable $th) {
            DB::rollback();
            return new JsonResponse([
                'error' => 'Error updating role: ' . $th->getMessage()
            ], 500);
        }
    }

    /**
     *
     * @param RoleUpdateRequest $request
     * @param Role $role
     * @return JsonResponse
     */
    public function update(RoleUpdateRequest $request, Role $role): JsonResponse
    {
        $roleData = $request->validated();
        $permissionNames = $this->normalizePermissionNames($roleData['permissions'] ?? []);

        if ($errorResponse = $this->validatePermissionNames($permissionNames)) {
            return $errorResponse;
        }

        DB::beginTransaction();

        try {
            // Fetch role with current permissions
            $role = Role::with('permissions')->findOrFail($roleData['id']);

            // Update the role name if it changed
            if ($role->name !== $roleData['name']) {
                $role->name = $roleData['name'];
            }

            if ($role->guard_name !== 'web') {
                $role->guard_name = 'web';
            }

            // New permissions from request (default to empty array)
            $newPermissionNames = $permissionNames;

            // Sync permissions if they have changed
            if ($role->isDirty()) {
                $role->save();
            }
            $role->syncPermissions($newPermissionNames);

            DB::commit();

            // Reload permissions for response
            $role->load('permissions');
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return new JsonResponse([
                'message' => 'Role updated successfully',
                'role' => RoleResponse::one($role),
            ], 201);

        } catch (\Throwable $th) {
            DB::rollback();

            return new JsonResponse([
                'error' => 'Error updating role: ' . $th->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Role  $role
     * @return JsonResponse
     */
    public function destroy(int $roleId): JsonResponse
    {
        try {
            DB::beginTransaction();

            $role = Role::findOrFail($roleId);

            // Prevent deleting default role (optional safeguard)
            if ($role->name === 'employee') {
                return new JsonResponse(['message' => 'Default role cannot be deleted'], 400);
            }

            // Find default role for same company
            $defaultRole = Role::where('name', 'employee')
                                ->first();

            if (!$defaultRole) {
                DB::rollBack();
                return new JsonResponse(['message' => 'Default role not found in this company'], 400);
            }

            // Reassign users BEFORE deleting the role
            foreach ($role->users as $user) {

                $user->syncRoles([$defaultRole->name]);
            }

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            // Now safe to delete
            $role->delete();

            DB::commit();

            return new JsonResponse(['message' => 'Role deleted and users reassigned successfully'], 201);

        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return new JsonResponse(['message' => $e->getMessage()], 404);

        } catch (\Throwable $th) {
            DB::rollBack();
            return new JsonResponse(['message' => $th->getMessage()], 500);
        }
    }

    /**
     *
     * @param RoleService $service
     * @return JsonResponse
     */
    public function getRolesDropdown(RoleService $service): JsonResponse
    {
        $roles = $service->getRolesByRules();

        return new JsonResponse(RolesDropdownResponse::many($roles));

    }

    /**
     *
     * @return JsonResponse
     */
    public function getRoles(RoleService $service): JsonResponse
    {
        $roles =  $roles = $service->getRolesByRules();

        return new JsonResponse(RoleResponse::many($roles));
    }

    private function normalizePermissionNames(array $permissions): array
    {
        return collect($permissions)
            ->map(function ($value) {
                return is_string($value) ? trim($value) : '';
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function validatePermissionNames(array $permissionNames): ?JsonResponse
    {
        if (empty($permissionNames)) {
            return new JsonResponse([
                'errors' => [
                    'permissions' => ['Select at least one permission.'],
                ],
            ], 422);
        }

        $permissions = Permission::query()
            ->whereIn('name', $permissionNames)
            ->get(['name', 'guard_name']);

        $existingNames = $permissions->pluck('name')->all();
        $missing = array_values(array_diff($permissionNames, $existingNames));

        if (!empty($missing)) {
            return new JsonResponse([
                'errors' => [
                    'permissions' => ['Invalid permissions: ' . implode(', ', $missing)],
                ],
            ], 422);
        }

        Permission::query()
            ->whereIn('name', $permissionNames)
            ->where('guard_name', '!=', 'web')
            ->update(['guard_name' => 'web']);

        return null;
    }

    // /**
    //  *
    //  * @return Collection
    //  */
    // public function allRoles(): Collection
    // {
    //     $user = auth()->user();
    //     $companyId = $user->company_id; // current user's company

    //     // Fetch current company + direct children
    //     $companies = Company::with([
    //         'roles' => function ($query) use ($user) {
    //             $roleName = $user->roles->pluck('name')->first();

    //             if ($roleName !== 'superadmin') {
    //                 $query->where('name', '<>', 'superadmin');
    //             }

    //             $query->with('permissions:id,name');
    //         }
    //     ])
    //     ->get(['id', 'name']);

    //     return $companies;
    // }
}
