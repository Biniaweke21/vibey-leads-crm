<?php

namespace Database\Factories;

use App\Enums\ClientStatus;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'industry' => fake()->randomElement(['Food & Beverage', 'Logistics & Trade', 'Construction', 'Travel & Tourism', 'Retail', 'Technology']),
            'city' => fake()->randomElement(['Addis Ababa', 'Hawassa', 'Mekelle', 'Bahir Dar', 'Jimma', 'Gondar']),
            'status' => ClientStatus::Active,
            'last_interaction_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
