<?php

namespace Tests\Feature\Api;

use App\Models\Area;
use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeviceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_devices_with_their_relations(): void
    {
        Sanctum::actingAs(User::factory()->create());
        Device::factory()->count(2)->create();

        $response = $this->getJson('/api/devices');

        $response->assertOk()->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [['id', 'name', 'status', 'area', 'device_type', 'device_model']]]);
    }

    public function test_it_creates_a_device(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $area = Area::factory()->create();

        $response = $this->postJson('/api/devices', [
            'name' => 'Foco sala',
            'area_id' => $area->id,
        ]);

        $response->assertCreated()->assertJsonPath('data.name', 'Foco sala');
        $this->assertDatabaseHas('devices', ['name' => 'Foco sala', 'area_id' => $area->id]);
    }

    public function test_the_api_token_is_never_exposed_in_responses(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $device = Device::factory()->create();

        $response = $this->getJson("/api/devices/{$device->id}");

        $response->assertOk()->assertJsonMissingPath('data.api_token');
    }

    public function test_it_updates_a_device(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $device = Device::factory()->create();

        $response = $this->putJson("/api/devices/{$device->id}", [
            'name' => 'Refrigerador cocina',
            'area_id' => $device->area_id,
            'status' => 'maintenance',
        ]);

        $response->assertOk()->assertJsonPath('data.status', 'maintenance');
    }

    public function test_it_deletes_a_device(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $device = Device::factory()->create();

        $response = $this->deleteJson("/api/devices/{$device->id}");

        $response->assertNoContent();
    }

    public function test_it_regenerates_the_device_api_token(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $device = Device::factory()->create();
        $originalToken = $device->api_token;

        $response = $this->postJson("/api/devices/{$device->id}/regenerate-token");

        $response->assertOk();
        $this->assertNotEquals($originalToken, $device->fresh()->api_token);
    }
}
