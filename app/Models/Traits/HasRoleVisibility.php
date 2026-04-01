<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Builder;

trait HasRoleVisibility
{
    public function scopeVisibleTo(Builder $query, $user): Builder
    {
        // Super admin sees everything
        if ($user->hasRole('super-admin')) {
            return $query;
        }

        // Department admin hides super admins and filters by department_id
        if ($user->hasRole('department-admin')) {

            // Hide super admins for models that have a roles() relationship
            if (method_exists($this, 'roles')) {
                $query->whereDoesntHave('roles', function ($q) {
                    $q->where('name', 'super-admin');
                });
            }

            if ($this instanceof \App\Models\Department) {
                return $query->where('id', $user->department_id);
            }

            if ($this instanceof \App\Models\Course) {
                return $query;
            }

            // Filter by department_id if column exists
            if ($this->hasColumn('department_id')) {
                $query->where('department_id', $user->department_id);
            }

            return $query;
        }

        // Company admin hides super/department admins and filters by company_id
        if ($user->hasRole('company-admin')) {

            if (method_exists($this, 'roles')) {
                $query->whereDoesntHave('roles', function ($q) {
                    $q->whereIn('name', ['super-admin', 'department-admin']);
                });
            }

            if ($this->hasColumn('company_id')) {
                $query->where('company_id', $user->company_id);
            }

            return $query;
        }

        // Default: only self (for User model)
        if ($this instanceof \App\Models\User) {
            return $query->where('id', $user->id);
        }

        // Fallback: return nothing
        return $query->whereRaw('0 = 1');
    }

    /**
     * Helper: check if model contains a column
     */
    private function hasColumn($column): bool
    {
        return in_array($column, $this->getFillable()) ||
               Schema::hasColumn($this->getTable(), $column);
    }
}
