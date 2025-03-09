<?php

namespace Database\Factories;

use App\Models\Test;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Test>
 */
class TestFactory extends Factory
{
    protected $model = Test::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'status' => $this->faker->randomElement(['pending', 'running', 'completed', 'failed']),
            'execution_time' => $this->faker->randomFloat(2, 0.1, 10.0),
            'result' => [
                'passed' => $this->faker->numberBetween(0, 10),
                'failed' => $this->faker->numberBetween(0, 5),
                'errors' => $this->faker->randomElements(['timeout', 'invalid_response'], 2)
            ]
        ];
    }
}
