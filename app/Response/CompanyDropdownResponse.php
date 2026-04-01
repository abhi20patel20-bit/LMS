<?php

namespace App\Response;

use App\Models\Company;

class CompanyDropdownResponse
{
    /**
     * Format a single Company
     *
     * @param Company $Companies
     * @return array
     */
    public static function one(Company $Companies): array
    {
        return [
            'id'      => $Companies->id,
            'name'    => $Companies->name,
        ];
    }

    /**
     * Format a collection of Companiess
     *
     * @param iterable $Companiess
     * @return array
     */
    public static function many($Companiess): array
    {
        return collect($Companiess)->map(fn($Companies) => self::one($Companies))->toArray();
    }
}
