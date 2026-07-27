<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Agent>
 */
class AgentFactory extends Factory
{
    protected $model = Agent::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'business_name' => fake()->company(),
            'agent_type' => 'shipping_agent',
            'bio' => fake()->sentence(),
            'status' => 'pending',
            'rating' => 0,
            'reviews_count' => 0,
            'points' => 0,
            'completed_orders' => 0,
            'is_featured' => false,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => 'approved', 'verified_at' => now()]);
    }
}
