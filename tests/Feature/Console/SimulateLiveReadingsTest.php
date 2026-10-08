<?php

namespace Tests\Feature\Console;

use App\Models\ConsumptionReading;
use App\Models\Device;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimulateLiveReadingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_simulated_device_on_first_run(): void
    {
        $this->artisan('app:simulate-live-readings')->assertExitCode(0);

        $device = Device::where('name', 'Simulador en vivo')->first();

        $this->assertNotNull($device);
        $this->assertSame('on', $device->status);
        $this->assertSame(1, $device->consumptionReadings()->count());
    }

    public function test_it_reuses_the_existing_device_and_keeps_adding_readings(): void
    {
        $this->artisan('app:simulate-live-readings')->assertExitCode(0);
        $this->artisan('app:simulate-live-readings')->assertExitCode(0);
        $this->artisan('app:simulate-live-readings')->assertExitCode(0);

        $this->assertSame(1, Device::where('name', 'Simulador en vivo')->count());
        $this->assertSame(3, ConsumptionReading::count());
    }

    public function test_it_accepts_a_custom_device_name_and_base_watts(): void
    {
        $this->artisan('app:simulate-live-readings', ['--device' => 'Mi sensor', '--watts' => 100])
            ->assertExitCode(0);

        $device = Device::where('name', 'Mi sensor')->first();

        $this->assertNotNull($device);
        $reading = $device->consumptionReadings()->first();
        $this->assertNotNull($reading);
        // Primera lectura: debe quedar cerca de la base dado que no hay historial previo.
        $this->assertEqualsWithDelta(100, $reading->value, 60);
    }

    public function test_generated_values_stay_non_negative(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->artisan('app:simulate-live-readings', ['--watts' => 10])->assertExitCode(0);
        }

        $this->assertSame(0, ConsumptionReading::where('value', '<', 0)->count());
    }
}
