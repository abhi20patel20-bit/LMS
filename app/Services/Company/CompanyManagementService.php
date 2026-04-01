<?php

namespace App\Services\Company;

use Throwable;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;

class CompanyManagementService
{

    /**
     *
     * @return Collection|array
     */
    public function getCompanyByRules(): Collection|array
    {
        $user = auth()->user();

        $company = Company::query();

        if ($user->hasRole('super-admin')) {
            // Sees all companies
        } elseif ($user->hasRole('company-admin') || $user->hasRole('department-admin')) {
            $company->where('id', $user->company_id);
        } else {
            return [];
        }

        return $company->get();

    }

    /**
     * Update company with given data
     *
     * @param array $data
     * @return Company
     * @throws Throwable
     */
    public function updateCompany(array $data): Company
    {
        // Find the company
        $company = Company::findOrFail($data['id']);

        DB::beginTransaction();
        try {
            // Update fields if provided
            $company->name     = $data['name'] ?? $company->name;
            $company->email    = $data['email'] ?? $company->email;
            $company->settings = isset($data['settings'])
                ? json_encode($data['settings'])
                : $company->settings;
            $company->type     = $data['type'] ?? $company->type;
            $company->phone    = $data['phone'] ?? $company->phone;
            $company->address  = $data['address'] ?? $company->address;

            $company->save();

            DB::commit();

            return $company;
        } catch (Throwable $th) {
            DB::rollBack();
            throw $th; // Let the controller or Handler handle/report it
        }
    }

}
