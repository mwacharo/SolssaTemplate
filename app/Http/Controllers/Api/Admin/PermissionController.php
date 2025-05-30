<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\PermissionRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
// use Spatie\Permission\Models\Permission;

use App\Models\Permission; // ✅ Must be this one


class PermissionController extends Controller
{
    protected $permissions;

    public function __construct(PermissionRepository $permissions)
    {
        $this->permissions = $permissions;
    }

    public function index()
    {
        return response()->json($this->permissions->all());
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        Log::info('User creating permission', ['user_id' => $user?->id]);

        $request->validate([
            'name' => 'required|string|unique:permissions',
        ]);

        $permission = $this->permissions->create($request->only('name'));

        activity()
            ->causedBy($user)
            ->performedOn($permission)
            ->withProperties(['attributes' => $permission->toArray()])
            ->log("Created permission: {$permission->name}");

        return response()->json(['message' => 'Permission created.', 'permission' => $permission], 201);
    }




    // public function destroy(Permission $permission)
    // {
    //     $user = auth()->user();

    //     $permissionName = $permission->name;

    //     $this->permissions->delete($permission);

    //     Log::info('User deleted permission', ['user_id' => $user?->id, 'permission' => $permissionName]);

    //     activity()
    //         ->causedBy($user)
    //         ->withProperties(['name' => $permissionName])
    //         ->log("Deleted permission: {$permissionName}");

    //     return response()->json(['message' => 'Permission deleted.']);
    // }

    public function update(Request $request, Permission $permission)
    {
        $user = auth()->user();

        $request->validate([
            'name' => 'required|string|unique:permissions,name,' . $permission->id,
        ]);

        $updatedPermission = $this->permissions->update($permission, $request->only('name'));

        activity()
            ->causedBy($user)
            ->performedOn($updatedPermission)
            ->withProperties(['attributes' => $updatedPermission->toArray()])
            ->log("Updated permission: {$updatedPermission->name}");

        return response()->json(['message' => 'Permission updated.', 'permission' => $updatedPermission]);
    }

    public function destroy(Permission $permission)
    {
        $this->permissions->delete($permission);
        return response()->json(['message' => 'Permission deleted successfully']);
    }

}
