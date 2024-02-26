<?php

declare(strict_types=1);

namespace App\Services\Models;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public const EXCEPTION_AUTHENTICATED_USER_ONLY = 'Authenticated user only';
    public const EXCEPTION_USERNAME_EMPTY = 'Username cannot be empty';
    public const EXCEPTION_USERNAME_EXISTS = 'Pick another username';
    public const EXCEPTION_PASSWORD_EMPTY = 'Password cannot be empty';
    public const EXCEPTION_PASSWORD_MIN_LENGTH = 'Password should be composed by at least ' . self::PASSWORD_MIN_LENGTH. ' characters';

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
            throw new \Exception(self::EXCEPTION_AUTHENTICATED_USER_ONLY);
        }

        $this->isPasswordValid($newPassword);

        return (bool) User::whereId(Auth::id())->update([
            'password' => Hash::make($newPassword)
        ]);
    }

    public function isUsernameValid(string $username): true
    {
        if(empty($username)) {
            throw new \Exception(self::EXCEPTION_USERNAME_EMPTY);
        }

        if(User::where('username', $username)->exists()) {
            throw new \Exception(self::EXCEPTION_USERNAME_EXISTS);
        }

        return true;
    }

    public function isPasswordValid(string $password): true
    {
        if(empty($password)) {
            throw new \Exception(self::EXCEPTION_PASSWORD_EMPTY);
        }

        if(strlen($password) < self::PASSWORD_MIN_LENGTH) {
            throw new \Exception(self::EXCEPTION_PASSWORD_MIN_LENGTH);
        }

        return true;
    }
}
