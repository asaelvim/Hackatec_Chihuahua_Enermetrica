<?php

namespace Tests\Unit\Services;

use App\Models\ConsumptionReading;
use App\Models\Device;
use App\Services\DashboardSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardSummaryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_chart_range_returns_24_hourly_buckets(): void
    {
        $data = app(DashboardSummaryService::class)->consumptionChartData();

        $this->assertCount(24, $data['labels']);
        $this->assertCount(24, $data['datasets'][0]['data']);
    }

    public function test_one_hour_range_returns_60_minute_buckets_and_reflects_recent_readings(): void
    {
        $device = Device::factory()->create();

        ConsumptionReading::factory()->create([
            'device_id' => $device->id,
            'value' => 600,
            'read_at' => Carbon::now()->subMinutes(2),
        ]);

        $data = app(DashboardSummaryService::class)->consumptionChartData('1h');

        $this->assertCount(60, $data['labels']);
        $this->assertCount(60, $data['datasets'][0]['data']);
        $this->assertEquals('Consumo promedio (kW) — última hora', $data['datasets'][0]['label']);
        $this->assertGreaterThan(0, array_sum($data['datasets'][0]['data']));
    }

    public function test_an_unknown_range_falls_back_to_24h(): void
    {
        $data = app(DashboardSummaryService::class)->consumptionChartData('invalid');

        $this->assertCount(24, $data['labels']);
    }
}
