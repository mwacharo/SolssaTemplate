<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\RoleRepository;
use Illuminate\Http\Request;
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
        $roles = Role::with('permissions')->get()->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name'),
            ];
        });

        return response()->json($roles);
    }



    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|unique:roles']);

        $role = $this->roles->create($request->only('name'));

        return response()->json(['message' => 'Role created.', 'role' => $role], 201);
    }

    public function update(Request $request, Role $role)
    {
        $request->validate(['name' => 'required|string|unique:roles,name,' . $role->id]);

        $this->roles->update($role, $request->only('name'));

        return response()->json(['message' => 'Role updated.']);
    }

    public function destroy(Role $role)
    {
        $this->roles->delete($role);

        return response()->json(['message' => 'Role deleted.']);
    }
}
