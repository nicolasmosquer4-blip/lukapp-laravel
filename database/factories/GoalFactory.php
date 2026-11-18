<?php

namespace Database\Factories;

use App\Models\Goal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Goal>
 */
class GoalFactory extends Factory
{
    protected $model = Goal::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => ucfirst(fake()->words(2, true)),
            'icon' => fake()->randomElement(['🏖️', '🚗', '🏠', '📱', '✈️', '🎓', '💻']),
            'target' => 1000000,
            'saved' => 0,
            'deadline' => fake()->dateTimeBetween('+1 month', '+1 year')->format('Y-m-d'),
        ];
    }
}
