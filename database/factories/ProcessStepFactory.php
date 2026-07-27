<?php

namespace Database\Factories;

use App\Models\ProcessStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProcessStep>
 */
class ProcessStepFactory extends Factory
{
    protected $model = ProcessStep::class;

    public function definition(): array
    {
        return [
            'group' => 'fund_step',
            'icon' => 'shield',
            'title' => fake()->sentence(3),
            'body' => fake()->sentence(),
            'is_active' => true,
            'sort' => 0,
        ];
    }
}
