<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Stevebauman\Location\Facades\Location;

use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{


    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    // public function index()
    // {
    //     // $users = $this->userService->all();

    //     // Eager load roles and permissions for each user
    //     // $users->load(['roles', 'permissions']);


    //         $users=User::with(['roles', 'permissions'])->get();

    //         return response()->json([
    //             'data' => UserResource::collection($users),
    //             'message' => 'Users retrieved successfully'
    //         ]);


    // }



    public function index()
{
    // Dynamically get the authenticated user's current team ID or fallback to default (e.g., 1)
    $teamId = Auth::user()?->currentTeam->id ?? 1;

    // Set team context for Spatie roles/permissions
    app(PermissionRegistrar::class)->setPermissionsTeamId($teamId);

    // Load users with their roles and permissions under the correct team context
    $users = User::with(['roles', 'permissions','country'])->get();

    return response()->json([
        'data' => UserResource::collection($users),
        'message' => 'Users retrieved successfully'
    ]);
}

    public function store(UserStoreRequest $request)
    {
        // log info
        Log::info('Creating User:', $request->validated());
        $user = $this->userService->create($request->validated());

        $user->ownedTeams()->create([
            'name' => $user->name . "'s Team",
            'user_id' => $user->id,
            'personal_team' => true,
        ]);

        // Assign roles if provided
        if ($request->has('roles')) {
            // Always provide a valid team ID when syncing roles with teams
            $teamId = Auth::user()?->currentTeam->id ?? 1; // Fallback to default team ID (e.g., 1)
            $user->syncRoles($request->input('roles'), $teamId);
        }

        return new UserResource($user);
    }

    public function show($id)
    {
        return new UserResource($this->userService->find($id));
    }

    // public function update(UserUpdateRequest $request, $id)
    // {
    //     $user = $this->userService->update($id, $request->validated());

    //     // If roles are provided in the request, update them using Spatie
    //     if ($request->has('roles')) {
    //         // Optionally, handle team context if using teams
    //         $teamId = Auth::user()?->currentTeam->id ?? null;
    //         if ($teamId) {
    //             $user->syncRoles($request->input('roles'), $teamId);
    //         } else {
    //             $user->syncRoles($request->input('roles'));
    //         }
    //     }

    //     return new UserResource($user);
    // }


    public function update(UserUpdateRequest $request, $id)
    {
        $user = $this->userService->update($id, $request->validated());

        if ($request->has('role_id')) {
            // Assign a single role by ID
            $roleId = $request->input('role_id');
            $user->syncRoles([$roleId]);
        }

        return new UserResource($user);
    }


    public function destroy($id)
    {
        $this->userService->delete($id);
        return response()->json(['message' => 'User deleted successfully']);
    }



     // ✅ Suspend or Reactivate User
    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);
        $user->is_active = !$user->is_active;
        $user->save();

        $status = $user->is_active ? 'Reactivated' : 'Suspended';
        $this->logActivity("$status user account", $user);

        return response()->json(['message' => "User account {$status}"]);
    }

    // ✅ Send Password Reset Link
    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $status = Password::sendResetLink(['email' => $request->email]);

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'Reset link sent.']);
        }

        return response()->json(['message' => 'Unable to send reset link.'], 400);
    }

    // ✅ Change Password on Behalf of User
    public function forceChangePassword(Request $request, $id)
    {
        $request->validate(['password' => 'required|min:8|confirmed']);
        $user = User::findOrFail($id);
        $user->password = Hash::make($request->password);
        $user->remember_token = Str::random(60);
        $user->save();

        $this->logActivity('Password changed by admin', $user);
        return response()->json(['message' => 'Password updated successfully']);
    }

    // ✅ Log with Device, IP, Location
    protected function logActivity($action, $user)
    {
        // $location = Location::get(request()->ip());

        activity('user-management')
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties([
                'ip' => request()->ip(),
                'agent' => request()->userAgent(),
                'location' => [
                    // 'city' => $location?->cityName,
                    // 'country' => $location?->countryName,
                ]
            ])
            ->log($action);
    }
}
