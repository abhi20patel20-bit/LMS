<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Company;
use App\Models\Permission;
use Illuminate\Http\Request;
use App\Response\CompanyResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Response\CompanyDropdownResponse;
use App\Http\Requests\CompanyCreateRequest;
use App\Http\Requests\CompanyUpdateRequest;
use App\Services\Company\CompanyManagementService;

class CompanyController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:create companies', ['only' => ['create', 'store']]);
        $this->middleware('permission:read companies', ['only' => ['index', 'show']]);
        $this->middleware('permission:update companies', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete companies', ['only' => ['destroy', 'destroyBulk']]);
    }

    /**
     *
     * @return Response
     */
    public function index(): Response
    {
        return Inertia::render('Company/Index');
    }

    /**
     *
     * @param CompanyManagementService $service
     * @return JsonResponse
     */
    public function getCompanies(CompanyManagementService $service): JsonResponse
    {
        $companies = $service->getCompanyByRules();

        return new JsonResponse(CompanyResponse::many($companies));
    }


     /**
      *
      * @param CompanyManagementService $service
      * @return JsonResponse
      */
    public function getCompaniesDropdown(CompanyManagementService $service): JsonResponse
    {

        // Use the universal role-based scope
        $companies = $service->getCompanyByRules();

        return new JsonResponse(CompanyDropdownResponse::many($companies));
    }


    // /**
    //  *
    //  * @param Request $request
    //  * @return JsonResponse
    //  */
    // public function store(Request $request): JsonResponse
    // {
    //     $data = $request->validated();

    //     DB::beginTransaction();

    //     try {

    //         if (is_string($data['features'])) {
    //             $data['features'] = json_decode($data['features'], true);
    //         }

    //         // Make sure values are boolean
    //         $data['features'] = collect($data['features'])->map(function ($value) {
    //             return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    //         })->toArray();

    //         // Create company first to get its ID
    //         $company = Company::create([
    //             'name' => $data['name'],
    //             'theme_color' => $data['theme_color'] ?? null,
    //             'parent_id' => $data['parent_id'] ?? null,
    //             'features' => $data['features'],
    //         ]);

    //         $companyId = $company->id;
    //         $logoDir = "public/logo/{$companyId}";

    //         // Handle Light Logo
    //         if ($request->hasFile('light_logo')) {
    //             $lightLogoFile = $request->file('light_logo');
    //             $lightLogoPath = $lightLogoFile->storeAs($logoDir, 'light.' . $lightLogoFile->getClientOriginalExtension());
    //             $company->light_logo = str_replace('public/', 'storage/', $lightLogoPath); // URL-accessible path
    //         }

    //         // Handle Dark Logo
    //         if ($request->hasFile('dark_logo')) {
    //             $darkLogoFile = $request->file('dark_logo');
    //             $darkLogoPath = $darkLogoFile->storeAs($logoDir, 'dark.' . $darkLogoFile->getClientOriginalExtension());
    //             $company->dark_logo = str_replace('public/', 'storage/', $darkLogoPath); // URL-accessible path
    //         }

    //         $company->save();

    //         // 🔹 Create Roles for the Company
    //         $superadminRole = Role::create([
    //             'name' => 'superadmin',
    //             'guard_name' => 'web',
    //             'company_id' => $companyId, // ensure roles table has company_id
    //         ]);

    //         $operatorRole = Role::create([
    //             'name' => 'operator',
    //             'guard_name' => 'web',
    //             'company_id' => $companyId,
    //         ]);

    //         // 🔹 Assign Permissions
    //         // Superadmin → all permissions
    //         $allPermissions = Permission::pluck('name')->toArray();
    //         $superadminRole->givePermissionTo($allPermissions);

    //         // Operator → only selected features
    //         $selectedFeatures = $data['features'] ?? [];
    //         if (!empty($selectedFeatures)) {
    //             $permissions = Permission::where('name', 'like', '%read%')->pluck('name')->toArray();
    //             $operatorRole->givePermissionTo($permissions);
    //         }

    //         DB::commit();

    //         return new JsonResponse([
    //             'message' => 'Company created successfully with roles and permissions',
    //             'company' => $company
    //         ], 201);

    //     } catch (\Throwable $th) {
    //         DB::rollBack();

    //         return new JsonResponse([
    //             'message' => 'Failed to create company',
    //             'error' => $th->getMessage()
    //         ], 500);
    //     }
    // }

    /**
     * Update Company (Name, Email, Phone, Address Only)
     *
     * @param CompanyUpdateRequest $request
     * @param CompanyManagementService $company
     * @return JsonResponse
     */
    public function update(CompanyUpdateRequest $request, CompanyManagementService $service): JsonResponse
    {
        $data = $request->validated();

        try {
            $company = $service->updateCompany($data);

            return response()->json([
                'message' => 'Company updated successfully',
                'company' => CompanyResponse::one($company)
            ], 201);

        } catch (\Throwable $th) {
            report($th); // logs in database via Handler

            return response()->json([
                'message' => 'Failed to update company',
                'error' => 'Internal server error'
            ], 500);
        }
    }


    /**
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $company = Company::with('users')->findOrFail($id);

        DB::transaction(function () use ($company) {
            $company->users()->delete();
            $company->delete();
        });

        return new JsonResponse([
            'message' => 'Company and related users deleted successfully'
        ], 200);
    }

}
