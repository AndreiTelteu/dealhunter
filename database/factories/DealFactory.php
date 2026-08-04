<?php

namespace Database\Factories;

use App\Models\HuntedDeal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Deal>
 */
class DealFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hunted_deal_id' => HuntedDeal::factory(),
            'external_id' => fake()->unique()->numerify('#########'),
            'url' => fake()->url(),
            'title' => fake()->sentence(4),
            'price_amount' => fake()->numberBetween(100, 5000),
            'price_currency' => 'RON',
            'price_raw' => null,
            'description' => fake()->optional()->paragraph(),
            'image_urls' => null,
            'location' => fake()->optional()->city(),
            'seller_name' => fake()->optional()->userName(),
            'seller_url' => null,
            'posted_at' => null,
            'matches_intent' => true,
            'likely_working' => true,
            'confidence' => 0.9,
            'last_seen_at' => now(),
        ];
    }
}
