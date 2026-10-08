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

    public function test_it_lists_the_five_relay_channels_with_a_valid_device_token(): void
    {
        $device = Device::factory()->create();

        $response = $this->getJson(
            "/api/devices/{$device->id}/relay-channels",
            ['Authorization' => 'Bearer '.$device->api_token]
        );

        $response->assertOk();
        $this->assertCount(5, $response->json('data'));
        $this->assertSame([1, 2, 3, 4, 5], collect($response->json('data'))->pluck('channel')->all());
        $this->assertSame('off', $response->json('data.0.desired_state'));
    }

    public function test_it_rejects_listing_without_a_valid_token(): void
    {
        $device = Device::factory()->create();

        $this->getJson("/api/devices/{$device->id}/relay-channels")->assertUnauthorized();
    }

    public function test_it_reflects_a_desired_state_set_from_the_admin_panel(): void
    {
        $device = Device::factory()->create();
        $device->ensureRelayChannels();
        $device->relayChannels()->where('channel', 3)->update(['desired_state' => 'on']);

        $response = $this->getJson(
            "/api/devices/{$device->id}/relay-channels",
            ['Authorization' => 'Bearer '.$device->api_token]
        );

        $response->assertOk();
        $this->assertSame('on', collect($response->json('data'))->firstWhere('channel', 3)['desired_state']);
    }

    public function test_the_device_can_acknowledge_the_state_it_actually_applied(): void
    {
        $device = Device::factory()->create();
        $device->ensureRelayChannels();

        $response = $this->postJson(
            "/api/devices/{$device->id}/relay-channels/2/ack",
            ['state' => 'on'],
            ['Authorization' => 'Bearer '.$device->api_token]
        );

        $response->assertOk()->assertJsonPath('data.reported_state', 'on');

        $this->assertDatabaseHas('device_relay_channels', [
            'device_id' => $device->id,
            'channel' => 2,
            'reported_state' => 'on',
        ]);
    }

    public function test_ack_rejects_an_invalid_state(): void
    {
        $device = Device::factory()->create();
        $device->ensureRelayChannels();

        $response = $this->postJson(
            "/api/devices/{$device->id}/relay-channels/2/ack",
            ['state' => 'maybe'],
            ['Authorization' => 'Bearer '.$device->api_token]
        );

        $response->assertUnprocessable()->assertJsonValidationErrors('state');
    }

    public function test_ack_rejects_an_invalid_channel_number(): void
    {
        $device = Device::factory()->create();
        $device->ensureRelayChannels();

        $response = $this->postJson(
            "/api/devices/{$device->id}/relay-channels/9/ack",
            ['state' => 'on'],
            ['Authorization' => 'Bearer '.$device->api_token]
        );

        $response->assertNotFound();
    }

    public function test_toggling_a_relay_from_the_admin_panel_is_recorded_with_the_user(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create();
        $device->ensureRelayChannels();

        $this->actingAs($user);

        Volt::test('pages.devices.index')
            ->call('manageRelays', $device->id)
            ->call('toggleRelay', 4, 'on');

        $this->assertDatabaseHas('device_relay_channels', [
            'device_id' => $device->id,
            'channel' => 4,
            'desired_state' => 'on',
            'commanded_by' => $user->id,
        ]);
    }
}
