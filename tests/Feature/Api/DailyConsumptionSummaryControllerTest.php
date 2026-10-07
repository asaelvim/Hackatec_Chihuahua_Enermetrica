<?php

namespace Tests\Feature\Api;

use App\Models\DailyConsumptionSummary;
use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DailyConsumptionSummaryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_summaries(): void
    {
        Sanctum::actingAs(User::factory()->create());
        DailyConsumptionSummary::factory()->count(3)->create();

        $response = $this->getJson('/api/daily-consumption-summaries');

        $response->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_it_filters_summaries_by_device(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $device = Device::factory()->create();
        DailyConsumptionSummary::factory()->for($device)->create();
        DailyConsumptionSummary::factory()->create();

        $response = $this->getJson("/api/daily-consumption-summaries?device_id={$device->id}");

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_it_filters_summaries_by_date_range(): void
    {
        Sanctum::actingAs(User::factory()->create());
        DailyConsumptionSummary::factory()->create(['date' => '2026-01-01']);
        DailyConsumptionSummary::factory()->create(['date' => '2026-02-01']);

        $response = $this->getJson('/api/daily-consumption-summaries?from=2026-01-15&to=2026-02-15');

        $response->assertOk()->assertJsonCount(1, 'data');
    }
}
