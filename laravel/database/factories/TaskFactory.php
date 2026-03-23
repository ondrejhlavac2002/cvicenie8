<?php

namespace Database\Factories;

use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'is_done' => fake()->boolean(30),
            'due_at' => fake()->optional()->dateTimeBetween('now', '+14 days'),
        ];
    }
}
