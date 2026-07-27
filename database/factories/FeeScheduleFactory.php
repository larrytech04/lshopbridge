<?php

namespace Database\Factories;

use App\Models\Fee;
use App\Models\FeeSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeeSchedule>
 */
class FeeScheduleFactory extends Factory
{
    protected $model = FeeSchedule::class;

    public function definition(): array
    {
        return [
            'fee_id' => Fee::factory(),
            'new_value' => 3.0,
            'effective_start_date' => now()->addDays(3),
            'effective_end_date' => null,
            'status' => 'scheduled',
            'reason' => 'Test schedule',
        ];
    }
}
