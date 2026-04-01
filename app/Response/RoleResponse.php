<?php

namespace App\Response;

use App\Models\Permission;
use App\Models\Role;

class RoleResponse
{
    /**
     * Format a single role
     * Role
     * @param Role $role
     * @return array
     */
    public static function one(Role $role): array
    {
        return [
            'id'      => $role->id,
            'name'    => $role->name,
            'permissions' => $role->permissions
        ];
    }

    /**
     * Format a collection of roles
     *
     * @param iterable $roles
     * @return array
     */
    public static function many($roles): array
    {
        return collect($roles)->map(fn($role) => self::one($role))->toArray();
    }
}
