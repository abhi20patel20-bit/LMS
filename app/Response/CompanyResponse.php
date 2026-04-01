<?php

namespace App\Response;

use App\Models\Company;

class CompanyResponse
{
    /**
     * Format a single Company
     *
     * @param Company $Companies
     * @return array
     */
    public static function one(Company $company): array
    {
        return [
            'id'            => $company->id,
            'name'          => $company->name,
            'email'         => $company->email,
            'phone'         => $company->phone,
            'type'          => $company->type,
            'settings'      => json_decode($company->settings),
            'address'       => $company->address,
        ];
    }

    /**
     * Format a collection of Companiess
     *
     * @param iterable $Companiess
     * @return array
     */
    public static function many($companies): array
    {
        return collect($companies)->map(fn($company) => self::one($company))->toArray();
    }
}
