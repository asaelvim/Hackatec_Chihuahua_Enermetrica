<?php

namespace Database\Factories;

use App\Models\Schedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Schedule>
 */
class ScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'scope' => 'company',
            'area_id' => null,
            'device_id' => null,
            'start_time' => '22:00:00',
            'end_time' => '06:00:00',
            'weekdays' => '0,1,2,3,4,5,6',
            'is_active' => true,
        ];
    }
}
