<?php

namespace App\Repositories;

use Spatie\Permission\Models\Permission;

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

    public function delete(Permission $permission)
    {
        return $permission->delete();
    }
}
