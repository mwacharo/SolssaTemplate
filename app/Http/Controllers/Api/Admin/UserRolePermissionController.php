<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


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


    public function assignRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        $role = $validated['role'];

        if ($role === 'Super Admin') {
            // ✅ Assign globally (no team_id)
            app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        } else {
            // ✅ Assign under current team
            $teamId = auth()->user()?->current_team_id;

            if (!$teamId) {
                return response()->json(['error' => 'No team context found for current user.'], 422);
            }

            app(PermissionRegistrar::class)->setPermissionsTeamId($teamId);
        }

        $user->assignRole($role);

        return response()->json(['message' => 'Role assigned successfully.']);
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
        $validator = Validator::make($request->all(), [
            'permission' => 'required|string|exists:permissions,name',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user->givePermissionTo($request->input('permission'));

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
}
