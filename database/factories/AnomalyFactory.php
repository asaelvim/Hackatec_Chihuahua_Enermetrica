<?php

namespace Database\Factories;

use App\Models\Anomaly;
use App\Models\ConsumptionReading;
use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Anomaly>
 */
class AnomalyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $device = Device::factory()->create();

        return [
            'device_id' => $device->id,
            'consumption_reading_id' => ConsumptionReading::factory()->for($device),
            'z_score' => fake()->randomFloat(4, 2, 5),
            'value' => fake()->randomFloat(2, 1500, 3000),
            'notified_at' => null,
        ];
    }
}
