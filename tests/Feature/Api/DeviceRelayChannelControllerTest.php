<?php

namespace Tests\Feature\Api;

use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class DeviceRelayChannelControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_the_devices_controlled_by_each_relay_channel_with_a_valid_device_token(): void
    {
        $controller = Device::factory()->create();
        $load = Device::factory()->controlledBy($controller, 1)->create(['status' => 'off']);

        $response = $this->getJson(
            "/api/devices/{$controller->id}/relay-channels",
            ['Authorization' => 'Bearer '.$controller->api_token]
        );

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame(1, $response->json('data.0.channel'));
        $this->assertSame($load->name, $response->json('data.0.label'));
        $this->assertSame('off', $response->json('data.0.desired_state'));
    }

    public function test_it_rejects_listing_without_a_valid_token(): void
    {
        $device = Device::factory()->create();

        $this->getJson("/api/devices/{$device->id}/relay-channels")->assertUnauthorized();
    }

    public function test_it_reflects_a_desired_state_set_from_the_admin_panel(): void
    {
        $controller = Device::factory()->create();
        Device::factory()->controlledBy($controller, 3)->create(['status' => 'on']);

        $response = $this->getJson(
            "/api/devices/{$controller->id}/relay-channels",
            ['Authorization' => 'Bearer '.$controller->api_token]
        );

        $response->assertOk();
        $this->assertSame('on', collect($response->json('data'))->firstWhere('channel', 3)['desired_state']);
    }

    public function test_an_offline_or_maintenance_device_is_reported_as_desired_off(): void
    {
        $controller = Device::factory()->create();
        Device::factory()->controlledBy($controller, 5)->create(['status' => 'maintenance']);

        $response = $this->getJson(
            "/api/devices/{$controller->id}/relay-channels",
            ['Authorization' => 'Bearer '.$controller->api_token]
        );

        $response->assertOk();
        $this->assertSame('off', collect($response->json('data'))->firstWhere('channel', 5)['desired_state']);
    }

    public function test_the_device_can_acknowledge_the_state_it_actually_applied(): void
    {
        $controller = Device::factory()->create();
        $load = Device::factory()->controlledBy($controller, 2)->create(['status' => 'on']);

        $response = $this->postJson(
            "/api/devices/{$controller->id}/relay-channels/2/ack",
            ['state' => 'on'],
            ['Authorization' => 'Bearer '.$controller->api_token]
        );

        $response->assertOk()->assertJsonPath('data.reported_state', 'on');

        $this->assertDatabaseHas('devices', [
            'id' => $load->id,
            'reported_status' => 'on',
        ]);
    }

    public function test_ack_rejects_an_invalid_state(): void
    {
        $controller = Device::factory()->create();
        Device::factory()->controlledBy($controller, 2)->create();

        $response = $this->postJson(
            "/api/devices/{$controller->id}/relay-channels/2/ack",
            ['state' => 'maybe'],
            ['Authorization' => 'Bearer '.$controller->api_token]
        );

        $response->assertUnprocessable()->assertJsonValidationErrors('state');
    }

    public function test_ack_rejects_an_invalid_channel_number(): void
    {
        $controller = Device::factory()->create();

        $response = $this->postJson(
            "/api/devices/{$controller->id}/relay-channels/9/ack",
            ['state' => 'on'],
            ['Authorization' => 'Bearer '.$controller->api_token]
        );

        $response->assertNotFound();
    }

    public function test_toggling_a_relay_controlled_device_from_the_admin_panel_is_recorded_with_the_user(): void
    {
        $user = User::factory()->create();
        $controller = Device::factory()->create();
        $load = Device::factory()->controlledBy($controller, 4)->create(['status' => 'off']);

        $this->actingAs($user);

        Volt::test('pages.devices.index')->call('toggleStatus', $load->id);

        $this->assertDatabaseHas('devices', [
            'id' => $load->id,
            'status' => 'on',
            'commanded_by' => $user->id,
        ]);
    }

    public function test_toggling_an_offline_or_maintenance_device_from_the_panel_has_no_effect(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['status' => 'maintenance']);

        $this->actingAs($user);

        Volt::test('pages.devices.index')->call('toggleStatus', $device->id);

        $this->assertDatabaseHas('devices', [
            'id' => $device->id,
            'status' => 'maintenance',
        ]);
    }
}
