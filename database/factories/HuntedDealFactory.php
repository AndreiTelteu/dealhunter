<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HuntedDeal>
 */
class HuntedDealFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'search_term' => fake()->words(2, true),
            'is_active' => true,
            'notes' => fake()->optional()->sentence(),
            'last_crawled_at' => null,
        ];
    }
}
