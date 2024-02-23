<?php

declare(strict_types=1);

namespace App\Services\Models;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public const PASSWORD_MIN_LENGTH = 6;

    public function create(
        string $username,
        string $password
    ): User
    {
        $this->isUsernameValid($username);
        $this->isPasswordValid($password);

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

    public function isUsernameValid(string $username): true
    {
        if(empty($username)) {
            throw new \Exception('Username cannot be empty');
        }

        if(User::where('username', $username)->exists()) {
            throw new \Exception('Pick another username');
        }

        return true;
    }

    public function isPasswordValid(string $password): true
    {
        if(empty($password)) {
            throw new \Exception('Password cannot be empty');
        }

        if(strlen($password) < self::PASSWORD_MIN_LENGTH) {
            throw new \Exception('Password should be composed by at least ' . self::PASSWORD_MIN_LENGTH. ' characters');
        }

        return true;
    }
}
