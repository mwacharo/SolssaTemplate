<?php

namespace App\Policies;

use App\Models\User;

class UserRolePermissionPolicy
{
    /**
     * Only Admins can manage roles and permissions
     */
    public function manage(User $user): bool
    {
        return $user->hasRole('Admin');
    }

    /**
     * Assign role to a user
     */
    public function assignRole(User $user): bool
    {
        return $user->hasRole('Admin');
    }

    /**
     * Remove role from a user
     */
    public function removeRole(User $user): bool
    {
        return $user->hasRole('Admin');
    }

    /**
     * Assign permission to a user
     */
    public function assignPermission(User $user): bool
    {
        return $user->hasRole('Admin');
    }

    /**
     * Remove permission from a user
     */
    public function removePermission(User $user): bool
    {
        return $user->hasRole('Admin');
    }

    /**
     * View user permissions
     */
    public function viewPermissions(User $user): bool
    {
        return $user->hasRole('Admin');
    }
}