<?php

namespace App\Services;

use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password;
use App\Notifications\WelcomeUserNotification;

class UserService
{
    protected $userRepo;

    public function __construct(UserRepositoryInterface $userRepo)
    {
        $this->userRepo = $userRepo;
    }

    public function all()
    {
        return $this->userRepo->all();
    }

    public function find($id)
    {
        return $this->userRepo->find($id);
    }

  public function create(array $data)
{
    // Log input data (excluding sensitive info)
    Log::info('Creating user with data:', ['email' => $data['email'] ?? null]);

    // Generate a secure random password
    $plainPassword = Str::random(12); // 12 characters is strong enough
    $data['password'] = bcrypt($plainPassword);

    // Create the user via repository
    $user = $this->userRepo->create($data);
    Log::info('User created.', ['user_id' => $user->id, 'email' => $user->email]);

    // Send welcome notification with plain password
    $user->notify(new WelcomeUserNotification($plainPassword));

    return $user;
}


    public function update($id, array $data)
    {
        return $this->userRepo->update($id, $data);
    }

    public function delete($id)
    {
        return $this->userRepo->delete($id);
    }
}
