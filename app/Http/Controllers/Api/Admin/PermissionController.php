<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\PermissionRepository;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

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
        $request->validate([
            'name' => 'required|string|unique:permissions',
        ]);

        $permission = $this->permissions->create($request->only('name'));

        return response()->json(['message' => 'Permission created.', 'permission' => $permission], 201);
    }

    public function destroy(Permission $permission)
    {
        $this->permissions->delete($permission);

        return response()->json(['message' => 'Permission deleted.']);
    }
}
