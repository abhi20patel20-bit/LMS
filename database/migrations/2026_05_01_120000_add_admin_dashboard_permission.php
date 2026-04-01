<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::firstOrCreate([
            'name' => 'admin dashboard',
            'guard_name' => 'web',
        ]);

        if ($role = Role::where('name', 'super-admin')->first()) {
            $role->givePermissionTo($permission);
        }
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if ($permission = Permission::where('name', 'admin dashboard')
            ->where('guard_name', 'web')
            ->first()) {
            $permission->roles()->detach();
            $permission->delete();
        }
    }
};
