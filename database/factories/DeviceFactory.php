<?php

namespace Database\Factories;

use App\Models\Area;
use App\Models\Device;
use App\Models\DeviceModel;
use App\Models\DeviceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Device>
 */
class DeviceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'area_id' => Area::factory(),
            'device_type_id' => DeviceType::factory(),
            'device_model_id' => DeviceModel::factory(),
            'status' => fake()->randomElement(['on', 'off', 'offline', 'maintenance']),
            'last_reading_at' => fake()->dateTimeBetween('-1 hour', 'now'),
        ];
    }

    /**
     * Marca este dispositivo como controlado por el relevador $channel
     * (1-5) del dispositivo $controller (normalmente el ESP32).
     */
    public function controlledBy(Device $controller, int $channel): static
    {
        return $this->state(fn () => [
            'controller_device_id' => $controller->id,
            'relay_channel' => $channel,
        ]);
    }
}
