<?php

namespace Database\Factories;

use App\Models\ConsumptionReading;
use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConsumptionReading>
 */
class ConsumptionReadingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'device_id' => Device::factory(),
            'value' => fake()->randomFloat(2, 0, 2500),
            'read_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
