<?php

namespace App\Services\Role;

use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;

class RoleService
{

    /**
     *
     * @return Collection|array
     */
    public function getRolesByRules(): Collection|array
    {
        $user = auth()->user();

        $roles = Role::query()->with('permissions');

        if ($user->hasRole('super-admin')) {
            // Sees all roles
        } elseif ($user->hasRole('company-admin')) {
            $roles->whereNotIn('name', ['super-admin']);
        } elseif ($user->hasRole('department-admin')) {
            $roles->whereNotIn('name', ['super-admin', 'company-admin']);
        } else {
            return [];
        }

        return $roles->get();

    }

}
