<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private array $permissions = [
        'read job roles',
        'create job roles',
        'update job roles',
        'delete job roles',
    ];

    private array $roles = [
        'super-admin',
        'department-admin',
        'company-admin',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $createdPermissions = collect($this->permissions)->map(function ($permission) {
            return Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        });

        $targetRoles = Role::whereIn('name', $this->roles)->get();

        foreach ($targetRoles as $role) {
            $role->givePermissionTo($createdPermissions);
        }
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = Permission::whereIn('name', $this->permissions)
            ->where('guard_name', 'web')
            ->get();

        $targetRoles = Role::whereIn('name', $this->roles)->get();
        foreach ($targetRoles as $role) {
            $role->revokePermissionTo($permissions);
        }

        foreach ($permissions as $permission) {
            $permission->delete();
        }
    }
};
