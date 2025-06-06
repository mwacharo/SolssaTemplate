<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\Team;



use Spatie\Permission\PermissionRegistrar;


class UserRolePermissionController extends Controller
{
    //   public function assignRole(Request $request, User $user)
    // {
    //     $validated = $request->validate([
    //         'role' => 'required|string|exists:roles,name',
    //     ]);

    //     $teamId = auth()->user()?->current_team_id;

    //     if (!$teamId) {
    //         return response()->json(['error' => 'No team context found for current user.'], 422);
    //     }

    //     // ✅ Set the team context
    //     app(PermissionRegistrar::class)->setPermissionsTeamId($teamId);

    //     // ✅ Assign the role scoped to that team
    //     $user->assignRole($validated['role']);

    //     return response()->json(['message' => 'Role assigned successfully.']);
    // }


//   public function assignRole(Request $request, User $user)
// {
//     $validated = $request->validate([
//         'role' => 'required|string|exists:roles,name',
//     ]);

//     $roleName = $validated['role'];

//     // Get current team or fallback to default team_id = 1
//     $team = auth()->user()?->currentTeam ?? Team::find(1);

//     if (!$team) {
//         return response()->json(['error' => 'No team context found and default team does not exist.'], 422);
//     }

//     app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);

//     $user->assignRole($roleName);

//     app(PermissionRegistrar::class)->forgetCachedPermissions();

//     return response()->json([
//         'message' => "Role '{$roleName}' assigned successfully under Team ID {$team->id}."
//     ]);
// }



// public function assignRole(Request $request, User $user)
// {
//     $validated = $request->validate([
//         'role' => 'required|string|exists:roles,name',
//     ]);

//     $roleName = $validated['role'];

//     // Get current team or fallback to default team_id = 1
//     $team = auth()->user()?->currentTeam ?? Team::find(1);

//     if (!$team) {
//         return response()->json(['error' => 'No team context found and default team does not exist.'], 422);
//     }

//     app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);

//     // Revoke existing roles and assign the new one
//     $user->syncRoles($roleName);

//     app(PermissionRegistrar::class)->forgetCachedPermissions();

//     return response()->json([
//         'message' => "Role '{$roleName}' assigned successfully under Team ID {$team->id}."
//     ]);
// }



public function assignRole(Request $request, User $user)
{
    $validated = $request->validate([
        'role' => 'nullable|string|exists:roles,name',
        'role_id' => 'nullable|integer|exists:roles,id',
    ]);

    if (empty($validated['role']) && empty($validated['role_id'])) {
        return response()->json([
            'error' => 'Either role name or role ID must be provided.'
        ], 422);
    }

    // Resolve role name
    if (!empty($validated['role'])) {
        $roleName = $validated['role'];
    } else {
        $role = \Spatie\Permission\Models\Role::find($validated['role_id']);
        $roleName = $role->name;
    }

    // Get current team or fallback to default team_id = 1
    $team = auth()->user()?->currentTeam ?? Team::find(1);

    if (!$team) {
        return response()->json(['error' => 'No team context found and default team does not exist.'], 422);
    }

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);

    // Revoke existing roles and assign the new one
    $user->syncRoles($roleName);

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return response()->json([
        'message' => "Role '{$roleName}' assigned successfully under Team ID {$team->id}."
    ]);
}



    public function removeRole(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'role' => 'required|string|exists:roles,name',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user->removeRole($request->input('role'));

        return response()->json(['message' => 'Role removed successfully.']);
    }

    public function assignPermission(Request $request, User $user)
{
    Log::info('Assigning permission', [
        'user_id' => $user->id,
        'request_data' => $request->all(),
        'performed_by' => auth()->id(),
    ]);

    $validator = Validator::make($request->all(), [
        'permission' => 'sometimes|string|exists:permissions,name',
        'permission_id' => 'sometimes|integer|exists:permissions,id',
        'team_id' => 'nullable|integer|exists:teams,id',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $permissionName = $request->input('permission');
    if (!$permissionName && $request->filled('permission_id')) {
        $permission = \Spatie\Permission\Models\Permission::find($request->input('permission_id'));
        if (!$permission) {
            return response()->json(['error' => 'Permission not found.'], 404);
        }
        $permissionName = $permission->name;
    }

    if (!$permissionName) {
        return response()->json(['error' => 'Permission name or id must be provided.'], 422);
    }

    // ✅ Handle team context dynamically if teams are enabled
    if (config('permission.teams')) {
        if ($request->filled('team_id')) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($request->input('team_id'));
        } else {
            // Fallback global team (0 or your default value)
            $defaultTeamId = config('permission.default_team_id', 0);
            app(PermissionRegistrar::class)->setPermissionsTeamId($defaultTeamId);
        }
    }

    $user->givePermissionTo($permissionName);

    return response()->json(['message' => 'Permission assigned successfully.']);
}
    public function removePermission(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'permission' => 'required|string|exists:permissions,name',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user->revokePermissionTo($request->input('permission'));

        return response()->json(['message' => 'Permission removed successfully.']);
    }


    // fetch permissions of a user with spatie 

    public function getUserPermissions(User $user)
    {
        $permissions = $user->getAllPermissions()->pluck('name');

        return response()->json(['permissions' => $permissions]);
    }

}
