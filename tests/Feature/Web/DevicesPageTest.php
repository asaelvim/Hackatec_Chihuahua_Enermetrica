<?php

namespace Tests\Feature\Web;

use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class DevicesPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_for_an_authenticated_user(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        Device::factory()->create();

        $response = $this->actingAs($user)->get('/dispositivos');

        $response->assertOk()->assertSee('Dispositivos');
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dispositivos')->assertRedirect('/login');
    }

    public function test_editing_the_controller_to_offline_turns_off_its_relay_devices(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        $esp32 = Device::factory()->create(['status' => 'on']);
        $relay = Device::factory()->controlledBy($esp32, 1)->create(['status' => 'on']);

        Volt::test('pages.devices.index')
            ->call('edit', $esp32->id)
            ->set('status', 'offline')
            ->call('save');

        $this->assertSame('offline', $esp32->fresh()->status);
        $this->assertSame('off', $relay->fresh()->status);
    }

    public function test_editing_the_controller_to_on_turns_on_its_relay_devices(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        $esp32 = Device::factory()->create(['status' => 'off']);
        $relay = Device::factory()->controlledBy($esp32, 1)->create(['status' => 'off']);

        Volt::test('pages.devices.index')
            ->call('edit', $esp32->id)
            ->set('status', 'on')
            ->call('save');

        $this->assertSame('on', $esp32->fresh()->status);
        $this->assertSame('on', $relay->fresh()->status);
    }

    public function test_toggling_a_device_on_flashes_a_success_message(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        $device = Device::factory()->create(['status' => 'off']);

        $component = Volt::test('pages.devices.index')->call('toggleStatus', $device->id);

        $component->assertSee('se encendió correctamente');
        $component->assertSee($device->name);
        $this->assertSame('on', $device->fresh()->status);
    }

    public function test_toggling_an_untoggable_device_flashes_an_error_message(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        $device = Device::factory()->create(['status' => 'offline']);

        $component = Volt::test('pages.devices.index')->call('toggleStatus', $device->id);

        $component->assertSee('No se pudo cambiar el estado');
        $this->assertSame('offline', $device->fresh()->status);
    }
}
