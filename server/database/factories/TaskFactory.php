<?php

namespace Database\Factories;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'assigned_to' => null,
            'title' => fake()->randomElement(['Draft app flow', 'Product catalog import', 'Payment integration', 'Site progress dashboard', 'Content calendar']),
            'status' => fake()->randomElement(TaskStatus::cases()),
        ];
    }
}
