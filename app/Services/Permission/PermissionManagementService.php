<?php

namespace App\Services\Permission;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Collection;

class PermissionManagementService
{

    /**
     *
     * @return Collection|array
     */
    public function getPermissionByRules(): Collection|array
    {
        $user = auth()->user();

        $permission = Permission::query();

        if ($user->hasRole('super-admin')) {
            // Sees all permissions
        } elseif ($user->hasRole('company-admin')) {
            $permission->whereNotIn('name', [
                'read permissions', 'create permissions', 'update permissions', 'delete permissions',
                'create companies', 'delete companies'
            ]);
        } elseif ($user->hasRole('department-admin')) {
            $permission->whereNotIn('name', [
                'read permissions', 'create permissions', 'update permissions', 'delete permissions',
                'create companies', 'delete companies',
                'create departments', 'delete departments'
            ]);
        } else {
            return [];
        }

        return $permission->get();

    }

}
