<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index()
    {
        // $users = $this->userService->all();

        // Eager load roles and permissions for each user
        // $users->load(['roles', 'permissions']);


            $users=User::with(['roles', 'permissions'])->get();

            return response()->json([
                'data' => UserResource::collection($users),
                'message' => 'Users retrieved successfully'
            ]);


    }

    public function store(UserStoreRequest $request)
    {
        $user = $this->userService->create($request->validated());
        return new UserResource($user);
    }

    public function show($id)
    {
        return new UserResource($this->userService->find($id));
    }

    public function update(UserUpdateRequest $request, $id)
    {
        $user = $this->userService->update($id, $request->validated());
        return new UserResource($user);
    }

    public function destroy($id)
    {
        $this->userService->delete($id);
        return response()->json(['message' => 'User deleted successfully']);
    }
}
