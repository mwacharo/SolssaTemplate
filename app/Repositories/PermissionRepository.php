<?php

namespace App\Repositories;

// use Spatie\Permission\Models\Permission;

use App\Models\Permission;

class PermissionRepository
{
    public function all()
    {
        return Permission::all();
    }

    public function create(array $data)
    {
        return Permission::create($data);
    }

    public function update(Permission $permission, array $data)
    {
        $permission->update($data);
        return $permission;
    }

    public function delete(Permission $permission)
    {
        return $permission->delete();
    }
}
