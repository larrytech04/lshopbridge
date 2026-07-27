<?php

namespace Database\Factories;

use App\Models\Guide;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Guide>
 */
class GuideFactory extends Factory
{
    protected $model = Guide::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'slug' => \Illuminate\Support\Str::slug($title).'-'.fake()->unique()->numberBetween(1, 100000),
            'title' => $title,
            'category' => 'general',
            'difficulty' => 'beginner',
            'excerpt' => fake()->sentence(),
            'body' => fake()->paragraphs(3, true),
            'read_minutes' => 4,
            'views' => 0,
            'is_published' => true,
            'is_featured' => false,
            'sort' => 0,
        ];
    }
}
