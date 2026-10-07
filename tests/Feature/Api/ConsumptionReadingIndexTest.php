<?php

namespace Tests\Feature\Api;

use App\Models\ConsumptionReading;
use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConsumptionReadingIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_readings(): void
    {
        Sanctum::actingAs(User::factory()->create());
        ConsumptionReading::factory()->count(3)->create();

        $response = $this->getJson('/api/consumption-readings');

        $response->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_it_filters_readings_by_device(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $device = Device::factory()->create();
        ConsumptionReading::factory()->for($device)->create();
        ConsumptionReading::factory()->create();

        $response = $this->getJson("/api/consumption-readings?device_id={$device->id}");

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_it_shows_a_single_reading(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $reading = ConsumptionReading::factory()->create();

        $response = $this->getJson("/api/consumption-readings/{$reading->id}");

        $response->assertOk()->assertJsonPath('data.id', $reading->id);
    }
}
