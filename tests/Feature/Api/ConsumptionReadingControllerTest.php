<?php

namespace Tests\Feature\Api;

use App\Models\Device;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsumptionReadingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_valid_reading_is_stored_with_the_correct_device_token(): void
    {
        $device = Device::factory()->create(['status' => 'on']);

        $response = $this->postJson(
            "/api/devices/{$device->id}/readings",
            ['value' => 150.5],
            ['Authorization' => 'Bearer '.$device->api_token]
        );

        $response->assertCreated()
            ->assertJsonPath('data.value', 150.5)
            ->assertJsonPath('device_status', 'on')
            ->assertJsonPath('anomaly_detected', false);

        $this->assertDatabaseHas('consumption_readings', [
            'device_id' => $device->id,
            'value' => 150.5,
        ]);
    }

    public function test_it_rejects_requests_without_a_token(): void
    {
        $device = Device::factory()->create();

        $response = $this->postJson("/api/devices/{$device->id}/readings", ['value' => 100]);

        $response->assertUnauthorized();
    }

    public function test_it_rejects_requests_with_an_incorrect_token(): void
    {
        $device = Device::factory()->create();

        $response = $this->postJson(
            "/api/devices/{$device->id}/readings",
            ['value' => 100],
            ['Authorization' => 'Bearer token-incorrecto']
        );

        $response->assertUnauthorized();
    }

    public function test_it_rejects_a_value_above_the_sensor_maximum(): void
    {
        $device = Device::factory()->create();

        $response = $this->postJson(
            "/api/devices/{$device->id}/readings",
            ['value' => 99999],
            ['Authorization' => 'Bearer '.$device->api_token]
        );

        $response->assertUnprocessable()->assertJsonValidationErrors('value');
    }

    public function test_it_rejects_a_negative_value(): void
    {
        $device = Device::factory()->create();

        $response = $this->postJson(
            "/api/devices/{$device->id}/readings",
            ['value' => -10],
            ['Authorization' => 'Bearer '.$device->api_token]
        );

        $response->assertUnprocessable()->assertJsonValidationErrors('value');
    }
}
