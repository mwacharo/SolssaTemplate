<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\RoleRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
// use Spatie\Permission\Models\Role;
use App\Models\Role;

class RoleController extends Controller
{
    protected $roles;

    public function __construct(RoleRepository $roles)
    {
        $this->roles = $roles;
    }

    public function index()
    {
        Log::debug('Fetching all roles with permissions');
        $roles = Role::with('permissions')->get()->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name'),
            ];
        });

        Log::debug('Roles fetched', ['roles' => $roles]);
        return response()->json($roles);
    }

    public function store(Request $request)
    {
        Log::debug('Storing new role', ['request' => $request->all()]);
        $request->validate(['name' => 'required|string|unique:roles']);

        $role = $this->roles->create($request->only('name'));

        Log::debug('Role created', ['role' => $role]);
        return response()->json(['message' => 'Role created.', 'role' => $role], 201);
    }

    public function update(Request $request, Role $role)
    {
        Log::debug('Updating role', ['role_id' => $role->id, 'request' => $request->all()]);
        $request->validate([
            'name' => 'required|string|unique:roles,name,' . $role->id,
            'permissions' => 'sometimes|array',
            'permissions.*' => 'string'
        ]);

        $this->roles->update($role, $request->only('name'));

        // Assign permissions if provided
        if ($request->has('permissions')) {
            $role->syncPermissions($request->input('permissions'));
            Log::debug('Permissions synced', [
                'role_id' => $role->id,
                'permissions' => $request->input('permissions')
            ]);
        }

        Log::debug('Role updated', ['role_id' => $role->id]);
        return response()->json(['message' => 'Role updated.']);
    }

    public function destroy(Role $role)
    {
        Log::debug('Deleting role', ['role_id' => $role->id]);
        $this->roles->delete($role);

        Log::debug('Role deleted', ['role_id' => $role->id]);
        return response()->json(['message' => 'Role deleted.']);
    }
}
