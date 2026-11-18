<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Movement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Movement>
 */
class MovementFactory extends Factory
{
    protected $model = Movement::class;

    public function definition(): array
    {
        $type = fake()->randomElement(Movement::TYPES);
        $date = fake()->dateTimeBetween('-5 months', 'now');

        return [
            'user_id' => User::factory(),
            'type' => $type,
            'category_id' => fn (array $attrs) => Category::where('type', $attrs['type'])->inRandomOrder()->value('id')
                ?? Category::create(['name' => 'General '.$attrs['type'], 'type' => $attrs['type']])->id,
            'description' => fake()->words(2, true),
            'amount' => fake()->numberBetween(10000, 2000000),
            'date' => $date->format('Y-m-d'),
            'status' => Movement::statusForDate($date->format('Y-m-d')),
        ];
    }
}
