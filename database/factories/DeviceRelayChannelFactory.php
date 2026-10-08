<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\DeviceRelayChannel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeviceRelayChannel>
 */
class DeviceRelayChannelFactory extends Factory
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
            'channel' => $this->faker->numberBetween(1, 5),
            'label' => null,
            'desired_state' => 'off',
        ];
    }
}
