<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Company;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Http\Requests\PermissionIndexRequest;
use App\Http\Requests\PermissionStoreRequest;
use App\Http\Requests\PermissionUpdateRequest;
use App\Services\Permission\PermissionManagementService;

class PermissionController extends Controller
{
    public function __construct()
    {

        $this->middleware('permission:create permissions', ['only' => ['create', 'store']]);
        $this->middleware('permission:read permissions', ['only' => ['index', 'show']]);
        $this->middleware('permission:update permissions', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete permissions', ['only' => ['destroy', 'destroyBulk']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(PermissionIndexRequest $request)
    {
        return Inertia::render('Permission/Index');
    }

    public function store(PermissionStoreRequest $request)
    {
        DB::beginTransaction();
        try {
            $userCompany = auth()->user()->company_id;
            Permission::create([
                'name' => $request->name
            ]);

            DB::commit();
            return new JsonResponse(['message' => 'Permission Added Sucessfully'], 201);
        } catch (\Throwable $th) {
            DB::rollback();
            return new JsonResponse('error', 'Error creating ' .  $th->getMessage());
        }
    }

    public function update(PermissionUpdateRequest $request, Permission $permission)
    {
        DB::beginTransaction();
        try {
            $permission->update([
                'name' => $request->name
            ]);
            DB::commit();
            return new JsonResponse(['message' => 'Permission Update Sucessfully'], 201);
        } catch (\Throwable $th) {
            DB::rollback();
            return new JsonResponse('error', 'Error updating ' .  $th->getMessage());
        }
    }

    public function destroy(Permission $permission)
    {
        DB::beginTransaction();
        try {
            $superadmin = Role::whereName('superadmin')->first();
            $superadmin->revokePermissionTo([$permission->name]);
            $permission->delete();
            DB::commit();
            return back()->with('success', $permission->name. ' deleted successfully.');
        } catch (\Throwable $th) {
            DB::rollback();
            return back()->with('error', 'Error deleting ' . $permission->name . $th->getMessage());
        }
    }

    public function getAllPermissions(PermissionManagementService $service)
    {
        $permissions = $service->getPermissionByRules();

        return new JsonResponse(['permissions' => $permissions->toArray()]);
    }
}
