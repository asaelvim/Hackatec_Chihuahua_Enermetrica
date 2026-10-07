<?php

namespace Database\Factories;

use App\Models\DailyConsumptionSummary;
use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyConsumptionSummary>
 */
class DailyConsumptionSummaryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $min = fake()->randomFloat(2, 0, 500);
        $max = fake()->randomFloat(2, $min, $min + 2000);

        return [
            'device_id' => Device::factory(),
            'date' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'total_kwh' => fake()->randomFloat(3, 0, 50),
            'avg_watts' => fake()->randomFloat(2, $min, $max),
            'min_watts' => $min,
            'max_watts' => $max,
            'readings_count' => fake()->numberBetween(1, 2880),
        ];
    }
}
