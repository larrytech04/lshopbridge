<?php

namespace Database\Factories;

use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    protected $model = Page::class;

    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(2),
            'title' => fake()->sentence(3),
            'type' => 'legal',
            'excerpt' => fake()->sentence(),
            'body' => fake()->paragraphs(5, true),
            'is_published' => true,
            'version' => 1,
        ];
    }
}
