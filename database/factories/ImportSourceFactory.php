<?php

namespace Database\Factories;

use App\Models\ImportSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportSource>
 */
class ImportSourceFactory extends Factory
{
    protected $model = ImportSource::class;

    public function definition(): array
    {
        return [
            'code' => 'src-'.$this->faker->unique()->numberBetween(1000, 9999),
            'name' => 'Test Source',
            'group' => 'file',
            'status' => 'not_connected',
            'auto_sync' => 'manual',
            'is_active' => false,
        ];
    }
}
