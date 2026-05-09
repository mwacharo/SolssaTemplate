<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Spatie\Permission\PermissionRegistrar;

class UserPolicy
{
    use HandlesAuthorization;

    // ─────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────

    /**
     * Ensure team context is set before any role/permission check.
     * Call this at the top of every policy method.
     */
    private function setTeamContext(User $user): void
    {
        $teamId = $user->currentTeam?->id ?? 1;
        app(PermissionRegistrar::class)->setPermissionsTeamId($teamId);
    }

    /**
     * Super-admins bypass all policy checks automatically.
     * Remove this if you don't have a super-admin role.
     */
    public function before(User $user, string $ability): bool|null
    {
        $this->setTeamContext($user);

        if ($user->hasRole('Admin')) {
            return true;
        }

        return null; // Fall through to individual methods
    }

    // ─────────────────────────────────────────
    // Policy Methods
    // ─────────────────────────────────────────

    public function viewAny(User $user): bool
    {
        // before() already set team context
        return $user->hasPermissionTo('view users');
    }

    public function view(User $user, User $model): bool
    {
        // A user can always view their own profile;
        // admins can view any user within the same team
        return $user->id === $model->id
            || $user->hasPermissionTo('view users');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create users');
    }

    public function update(User $user, User $model): bool
    {
        // Users can update themselves; admins can update others
        return $user->id === $model->id
            || $user->hasPermissionTo('edit users');
    }

    public function delete(User $user, User $model): bool
    {
        // Cannot delete yourself
        if ($user->id === $model->id) {
            return false;
        }

        // Cannot delete a Super Admin
        if ($model->hasRole('Super Admin')) {
            return false;
        }

        return $user->hasPermissionTo('delete users');
    }

    public function suspend(User $user, User $model): bool
    {
        // Cannot suspend yourself
        if ($user->id === $model->id) {
            return false;
        }

        // Cannot suspend a Super Admin
        if ($model->hasRole('Admin')) {
            return false;
        }

        return $user->hasPermissionTo('suspend users');
    }

    public function resetPassword(User $user, User $model): bool
    {
        // Cannot force-reset a Super Admin's password
        if ($model->hasRole('Admin') && !$user->hasRole('Admin')) {
            return false;
        }

        return $user->hasPermissionTo('reset user passwords');
    }

    public function restore(User $user, User $model): bool
    {
        return $user->hasPermissionTo('restore users');
    }

    public function forceDelete(User $user, User $model): bool
    {
        // Only Super Admin can permanently delete — handled in before()
        // No regular admin should reach this
        return false;
    }
}
