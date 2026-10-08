<?php

namespace Tests\Unit\Models;

use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceTest extends TestCase
{
    use RefreshDatabase;

    public function test_turning_off_the_controller_via_toggle_status_turns_off_its_relay_devices(): void
    {
        $actor = User::factory()->create();
        $esp32 = Device::factory()->create(['status' => 'on']);
        $relayOn = Device::factory()->controlledBy($esp32, 1)->create(['status' => 'on']);
        $relayAlreadyOff = Device::factory()->controlledBy($esp32, 2)->create(['status' => 'off']);
        $unrelated = Device::factory()->create(['status' => 'on']);

        $esp32->toggleStatus($actor);

        $this->assertSame('off', $esp32->fresh()->status);
        $this->assertSame('off', $relayOn->fresh()->status);
        $this->assertSame($actor->id, $relayOn->fresh()->commanded_by);
        $this->assertSame('off', $relayAlreadyOff->fresh()->status);
        $this->assertSame('on', $unrelated->fresh()->status);
    }

    public function test_turning_on_the_controller_turns_on_all_its_relay_devices(): void
    {
        $actor = User::factory()->create();
        $esp32 = Device::factory()->create(['status' => 'off']);
        $relayOff = Device::factory()->controlledBy($esp32, 1)->create(['status' => 'off']);
        $relayMaintenance = Device::factory()->controlledBy($esp32, 2)->create(['status' => 'maintenance']);
        $unrelated = Device::factory()->create(['status' => 'off']);

        $esp32->toggleStatus($actor);

        $this->assertSame('on', $esp32->fresh()->status);
        $this->assertSame('on', $relayOff->fresh()->status);
        $this->assertSame($actor->id, $relayOff->fresh()->commanded_by);
        $this->assertSame('maintenance', $relayMaintenance->fresh()->status);
        $this->assertSame('off', $unrelated->fresh()->status);
    }

    public function test_turning_on_a_relay_device_turns_on_its_controller_without_affecting_siblings(): void
    {
        $esp32 = Device::factory()->create(['status' => 'off']);
        $relay = Device::factory()->controlledBy($esp32, 1)->create(['status' => 'off']);
        $sibling = Device::factory()->controlledBy($esp32, 2)->create(['status' => 'off']);

        $relay->toggleStatus();

        $this->assertSame('on', $relay->fresh()->status);
        $this->assertSame('on', $esp32->fresh()->status);
        $this->assertSame('off', $sibling->fresh()->status);
    }

    public function test_turning_off_the_last_active_relay_device_turns_off_its_controller(): void
    {
        $esp32 = Device::factory()->create(['status' => 'on']);
        $relayOn = Device::factory()->controlledBy($esp32, 1)->create(['status' => 'on']);
        $relayAlreadyOff = Device::factory()->controlledBy($esp32, 2)->create(['status' => 'off']);

        $relayOn->toggleStatus();

        $this->assertSame('off', $relayOn->fresh()->status);
        $this->assertSame('off', $esp32->fresh()->status);
        $this->assertSame('off', $relayAlreadyOff->fresh()->status);
    }

    public function test_turning_off_a_relay_device_does_not_affect_controller_if_siblings_are_still_on(): void
    {
        $esp32 = Device::factory()->create(['status' => 'on']);
        $relayOn = Device::factory()->controlledBy($esp32, 1)->create(['status' => 'on']);
        $siblingOn = Device::factory()->controlledBy($esp32, 2)->create(['status' => 'on']);

        $relayOn->toggleStatus();

        $this->assertSame('off', $relayOn->fresh()->status);
        $this->assertSame('on', $esp32->fresh()->status);
        $this->assertSame('on', $siblingOn->fresh()->status);
    }
}
