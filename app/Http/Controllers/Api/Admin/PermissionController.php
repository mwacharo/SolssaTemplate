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

      

        return response()->json(['message' => 'Permission created.', 'permission' => $permission], 201);
    }


    public function update(Request $request, Permission $permission)
    {
        $user = auth()->user();

        $request->validate([
            'name' => 'required|string|unique:permissions,name,' . $permission->id,
        ]);

        $updatedPermission = $this->permissions->update($permission, $request->only('name'));


        return response()->json(['message' => 'Permission updated.', 'permission' => $updatedPermission]);
    }

    public function destroy(Permission $permission)
    {
        $this->permissions->delete($permission);
        return response()->json(['message' => 'Permission deleted successfully']);
    }

}
