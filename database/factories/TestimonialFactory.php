<?php

namespace Database\Factories;

use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Testimonial>
 */
class TestimonialFactory extends Factory
{
    protected $model = Testimonial::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'source' => 'trustpilot',
            'rating' => 5.0,
            'review_date' => fake()->date(),
            'verified' => true,
            'text' => fake()->sentence(),
            'is_active' => true,
            'sort' => 0,
        ];
    }
}
