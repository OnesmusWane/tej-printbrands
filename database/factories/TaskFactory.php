<?php

namespace Database\Factories;

use App\Models\TaskColumn;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'task_column_id' => TaskColumn::factory(),
            'title' => fake()->sentence(4),
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
            'due_date' => now()->addDays(fake()->numberBetween(1, 14)),
            'assignee' => fake()->lexify('??'),
            'description' => fake()->paragraph(),
            'sort_order' => 0,
        ];
    }
}
