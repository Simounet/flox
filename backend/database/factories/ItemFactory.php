<?php

namespace Database\Factories;

use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Item>
 */
class ItemFactory extends Factory
{
    protected $model = Item::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
          'poster' => '',
          'released' => time(),
          'released_datetime' => now(),
          'src' => null,
        ];
    }

    public function movie(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'media_type' => 'movie',
        ]);
    }

    public function tv(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'media_type' => 'tv',
        ]);
    }
}
