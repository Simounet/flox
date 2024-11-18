<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $password;

        $username = strtolower(str_replace(' ', '', fake()->name));
        return [
            'username' => $username,
            'password' => $password ?: $password = bcrypt('secret'),
            'remember_token' => Str::random(10),
            'api_key' => null,
        ];
    }
}
