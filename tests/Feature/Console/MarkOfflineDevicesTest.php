<?php

namespace Tests\Feature\Console;

use App\Models\Device;
use App\Models\User;
use App\Notifications\DeviceWentOffline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MarkOfflineDevicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_marks_stale_devices_as_offline(): void
    {
        $staleDevice = Device::factory()->create([
            'status' => 'on',
            'last_reading_at' => Carbon::now()->subMinutes(30),
        ]);

        $this->artisan('app:mark-offline-devices')->assertExitCode(0);

        $this->assertEquals('offline', $staleDevice->fresh()->status);
    }

    public function test_it_does_not_touch_recently_reporting_devices(): void
    {
        $freshDevice = Device::factory()->create([
            'status' => 'on',
            'last_reading_at' => Carbon::now()->subMinutes(2),
        ]);

        $this->artisan('app:mark-offline-devices')->assertExitCode(0);

        $this->assertEquals('on', $freshDevice->fresh()->status);
    }

    public function test_it_does_not_override_devices_in_maintenance(): void
    {
        $device = Device::factory()->create([
            'status' => 'maintenance',
            'last_reading_at' => Carbon::now()->subHours(2),
        ]);

        $this->artisan('app:mark-offline-devices')->assertExitCode(0);

        $this->assertEquals('maintenance', $device->fresh()->status);
    }

    public function test_it_marks_devices_that_never_reported_and_are_old_enough(): void
    {
        $device = Device::factory()->create(['status' => 'on', 'last_reading_at' => null]);
        $device->forceFill(['created_at' => Carbon::now()->subMinutes(30)])->save();

        $this->artisan('app:mark-offline-devices')->assertExitCode(0);

        $this->assertEquals('offline', $device->fresh()->status);
    }

    public function test_it_respects_a_custom_threshold(): void
    {
        $device = Device::factory()->create([
            'status' => 'on',
            'last_reading_at' => Carbon::now()->subMinutes(10),
        ]);

        $this->artisan('app:mark-offline-devices', ['--minutes' => 5])->assertExitCode(0);

        $this->assertEquals('offline', $device->fresh()->status);
    }

    public function test_it_notifies_users_when_a_device_goes_offline(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $staleDevice = Device::factory()->create([
            'status' => 'on',
            'last_reading_at' => Carbon::now()->subMinutes(30),
        ]);

        $this->artisan('app:mark-offline-devices')->assertExitCode(0);

        Notification::assertSentTo(
            $user,
            DeviceWentOffline::class,
            fn ($notification) => $notification->toArray($user)['device_id'] === $staleDevice->id
        );
    }

    public function test_it_does_not_notify_when_no_device_goes_offline(): void
    {
        Notification::fake();

        User::factory()->create();
        Device::factory()->create([
            'status' => 'on',
            'last_reading_at' => Carbon::now()->subMinutes(2),
        ]);

        $this->artisan('app:mark-offline-devices')->assertExitCode(0);

        Notification::assertNothingSent();
    }
}
