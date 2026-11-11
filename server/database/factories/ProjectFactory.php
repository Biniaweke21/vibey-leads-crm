<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    public function definition(): array
    {
        $status = fake()->randomElement(ProjectStatus::cases());

        return [
            'client_id' => Client::factory(),
            'name' => fake()->randomElement(['Website & Brand Refresh', 'Mobile Ordering App', 'E-commerce Storefront', 'Custom Software Platform', 'SEO Campaign']),
            'description' => fake()->sentence(),
            'status' => $status,
            'progress' => $status === ProjectStatus::Completed ? 100 : fake()->numberBetween(0, 90),
            'budget' => fake()->numberBetween(20, 300) * 1000,
            'start_date' => fake()->dateTimeBetween('-40 days', '-5 days'),
            'end_date' => fake()->dateTimeBetween('+5 days', '+90 days'),
        ];
    }
}
