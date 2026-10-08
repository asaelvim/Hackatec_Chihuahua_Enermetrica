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

    public function test_turning_the_controller_back_on_does_not_affect_its_relay_devices(): void
    {
        $esp32 = Device::factory()->create(['status' => 'off']);
        $relay = Device::factory()->controlledBy($esp32, 1)->create(['status' => 'off']);

        $esp32->toggleStatus();

        $this->assertSame('on', $esp32->fresh()->status);
        $this->assertSame('off', $relay->fresh()->status);
    }
}
