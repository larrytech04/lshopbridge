<?php

namespace Database\Factories;

use App\Models\FeeExemption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeeExemption>
 */
class FeeExemptionFactory extends Factory
{
    protected $model = FeeExemption::class;

    public function definition(): array
    {
        return [
            'exemption_type' => 'customer',
            'target_value' => 'test',
            'reason' => 'Test exemption',
            'start_date' => now()->subDay(),
            'end_date' => null,
            'is_active' => true,
        ];
    }
}
