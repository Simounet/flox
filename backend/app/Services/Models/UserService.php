<?php

declare(strict_types=1);

namespace App\Services\Models;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function create(
        string $username,
        string $password
    ): User|false
    {
        if($this->userExists($username)) {
            return false;
        }

        $user = new User();
        $user->username = $username;
        $user->password = Hash::make($password);
        $user->save();
        return $user;
    }

    public function changePassword(
        string $newPassword
    ): bool
    {
        if(!Auth::check()) {
            return false;
        }

        return (bool) User::whereId(Auth::id())->update([
            'password' => Hash::make($newPassword)
        ]);
    }

    private function userExists(
        string $username
    ): bool
    {
        $existingUsersCount = User::where('username', $username)->count();
        return 0 < $existingUsersCount;
    }
}
