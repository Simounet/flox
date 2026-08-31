<?php

namespace Database\Factories;

use App\Models\ItemUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ItemUser>
 */
class ItemUserFactory extends Factory
{
    protected $model = ItemUser::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => 1,
            'item_id' => 1,
            'rating' => 1,
            'watchlist' => 0
        ];
    }
}
