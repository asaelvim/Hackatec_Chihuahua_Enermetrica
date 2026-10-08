<?php

namespace Tests\Feature\Console;

use App\Models\ConsumptionReading;
use App\Models\DailyConsumptionSummary;
use App\Models\Device;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SummarizeDailyConsumptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_summarizes_yesterdays_readings_by_default(): void
    {
        $device = Device::factory()->create();
        $yesterday = Carbon::yesterday()->setTime(12, 0);

        // La energía se integra respecto al tiempo real entre lecturas: la de
        // las 12:00 cuenta 1h a 100W, y la de las 13:00 persiste hasta el
        // final del día (aprox. 11h) a 300W.
        ConsumptionReading::factory()->for($device)->create(['value' => 100, 'read_at' => $yesterday]);
        ConsumptionReading::factory()->for($device)->create(['value' => 300, 'read_at' => $yesterday->copy()->addHour()]);

        $this->artisan('app:summarize-daily-consumption')->assertExitCode(0);

        $summary = DailyConsumptionSummary::where('device_id', $device->id)->first();

        $this->assertNotNull($summary);
        $this->assertEqualsWithDelta(3.4, (float) $summary->total_kwh, 0.001);
        $this->assertEquals(200, (float) $summary->avg_watts);
        $this->assertEquals(100, (float) $summary->min_watts);
        $this->assertEquals(300, (float) $summary->max_watts);
        $this->assertEquals(2, $summary->readings_count);
    }

    public function test_it_ignores_devices_without_readings_on_the_target_date(): void
    {
        $device = Device::factory()->create();
        ConsumptionReading::factory()->for($device)->create(['read_at' => Carbon::now()]);

        $this->artisan('app:summarize-daily-consumption')->assertExitCode(0);

        $this->assertDatabaseCount('daily_consumption_summaries', 0);
    }

    public function test_it_accepts_an_explicit_date_option(): void
    {
        $device = Device::factory()->create();
        $targetDate = Carbon::parse('2026-01-15');

        ConsumptionReading::factory()->for($device)->create([
            'value' => 500,
            'read_at' => $targetDate->copy()->setTime(8, 0),
        ]);

        $this->artisan('app:summarize-daily-consumption', ['--date' => '2026-01-15'])->assertExitCode(0);

        $this->assertDatabaseHas('daily_consumption_summaries', [
            'device_id' => $device->id,
            'date' => '2026-01-15',
        ]);
    }

    public function test_it_computes_kwh_using_the_real_time_between_readings(): void
    {
        // Lecturas cada 30 min con potencia constante de 1000W durante 24h:
        // la energía esperada es 1000W * 24h = 24 kWh, no 48 kWh (que es lo
        // que se obtendría sumando los watts de las 48 lecturas y dividiendo
        // entre 1000, ignorando que cada una representa solo 30 min).
        $device = Device::factory()->create();
        $dayStart = Carbon::yesterday()->startOfDay();

        for ($minutes = 0; $minutes < 24 * 60; $minutes += 30) {
            ConsumptionReading::factory()->for($device)->create([
                'value' => 1000,
                'read_at' => $dayStart->copy()->addMinutes($minutes),
            ]);
        }
        // Lectura que cierra el último intervalo del día.
        ConsumptionReading::factory()->for($device)->create([
            'value' => 1000,
            'read_at' => $dayStart->copy()->addDay(),
        ]);

        $this->artisan('app:summarize-daily-consumption')->assertExitCode(0);

        $summary = DailyConsumptionSummary::where('device_id', $device->id)->first();

        $this->assertNotNull($summary);
        $this->assertEqualsWithDelta(24.0, (float) $summary->total_kwh, 0.001);
        $this->assertEquals(48, $summary->readings_count);
    }

    public function test_it_updates_an_existing_summary_instead_of_duplicating_it(): void
    {
        $device = Device::factory()->create();
        $yesterday = Carbon::yesterday();

        ConsumptionReading::factory()->for($device)->create(['value' => 100, 'read_at' => $yesterday->copy()->setTime(9, 0)]);
        $this->artisan('app:summarize-daily-consumption')->assertExitCode(0);

        ConsumptionReading::factory()->for($device)->create(['value' => 900, 'read_at' => $yesterday->copy()->setTime(10, 0)]);
        $this->artisan('app:summarize-daily-consumption')->assertExitCode(0);

        $this->assertDatabaseCount('daily_consumption_summaries', 1);
        $summary = DailyConsumptionSummary::where('device_id', $device->id)->first();
        $this->assertEquals(2, $summary->readings_count);
    }
}
