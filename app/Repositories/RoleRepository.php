<?php

namespace App\Repositories;

use Spatie\Permission\Models\Role;

class RoleRepository
{
    public function all()
    {
        return Role::all();
    }

    public function create(array $data)
    {
        return Role::create($data);
    }

    public function update(Role $role, array $data)
    {
        return $role->update($data);
    }

    public function delete(Role $role)
    {
        return $role->delete();
    }
}
