<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\ClientContact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientContact>
 */
class ClientContactFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'name' => fake()->name(),
            'role' => fake()->randomElement(['CEO', 'Founder', 'Marketing Lead', 'Operations Manager', 'Owner']),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+251 9'.fake()->numerify('# ### ####'),
        ];
    }
}
