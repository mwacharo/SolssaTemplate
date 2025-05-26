<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserRolePermissionController extends Controller
{
    public function assignRole(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'role' => 'required|string|exists:roles,name',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user->assignRole($request->input('role'));

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
