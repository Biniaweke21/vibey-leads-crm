<?php

namespace Database\Factories;

use App\Models\Milestone;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Milestone>
 */
class MilestoneFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'title' => fake()->randomElement(['Discovery & wireframes', 'Design sign-off', 'Core build', 'Launch']),
            'due_date' => fake()->dateTimeBetween('-10 days', '+40 days'),
            'is_done' => fake()->boolean(40),
        ];
    }
}
