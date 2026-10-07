<?php

namespace Tests\Feature;

use App\Models\ConsumptionReading;
use App\Models\Device;
use App\Models\Schedule;
use App\Models\User;
use App\Notifications\AnomalyDetected;
use App\Services\ConsumptionIngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ConsumptionIngestionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_ingest_creates_reading_and_updates_device_last_reading_at(): void
    {
        $device = Device::factory()->create(['status' => 'on']);
        $readAt = Carbon::parse('2026-01-05 10:00:00');

        $result = app(ConsumptionIngestionService::class)->ingest($device, 150.5, $readAt);

        $this->assertDatabaseHas('consumption_readings', [
            'device_id' => $device->id,
            'value' => 150.5,
        ]);
        $this->assertTrue($readAt->equalTo($device->refresh()->last_reading_at));
        $this->assertInstanceOf(ConsumptionReading::class, $result['reading']);
    }

    public function test_ingest_turns_device_off_during_active_company_schedule(): void
    {
        $device = Device::factory()->create(['status' => 'on']);

        Schedule::factory()->create([
            'scope' => 'company',
            'start_time' => '00:00:00',
            'end_time' => '23:59:59',
            'weekdays' => '0,1,2,3,4,5,6',
            'is_active' => true,
        ]);

        app(ConsumptionIngestionService::class)->ingest($device, 100);

        $this->assertSame('off', $device->refresh()->status);
    }

    public function test_ingest_turns_device_on_when_no_schedule_applies(): void
    {
        $device = Device::factory()->create(['status' => 'off']);

        app(ConsumptionIngestionService::class)->ingest($device, 100);

        $this->assertSame('on', $device->refresh()->status);
    }

    public function test_ingest_keeps_maintenance_status_regardless_of_schedule(): void
    {
        $device = Device::factory()->create(['status' => 'maintenance']);

        app(ConsumptionIngestionService::class)->ingest($device, 100);

        $this->assertSame('maintenance', $device->refresh()->status);
    }

    public function test_ingest_flags_anomaly_when_zscore_exceeds_threshold(): void
    {
        $device = Device::factory()->create(['status' => 'on']);

        ConsumptionReading::factory()
            ->count(10)
            ->for($device)
            ->sequence(fn ($sequence) => ['value' => 90 + (($sequence->index % 5) * 5)])
            ->create(['read_at' => Carbon::now()->subDays(1)]);

        $result = app(ConsumptionIngestionService::class)->ingest($device, 5000);

        $this->assertNotNull($result['anomaly']);
        $this->assertDatabaseHas('anomalies', [
            'device_id' => $device->id,
            'consumption_reading_id' => $result['reading']->id,
        ]);
    }

    public function test_ingest_does_not_flag_anomaly_with_insufficient_history(): void
    {
        $device = Device::factory()->create(['status' => 'on']);

        ConsumptionReading::factory()->count(2)->for($device)->create([
            'value' => 100,
            'read_at' => Carbon::now()->subDays(1),
        ]);

        $result = app(ConsumptionIngestionService::class)->ingest($device, 5000);

        $this->assertNull($result['anomaly']);
    }

    public function test_ingest_notifies_users_when_an_anomaly_is_flagged(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $device = Device::factory()->create(['status' => 'on']);

        ConsumptionReading::factory()
            ->count(10)
            ->for($device)
            ->sequence(fn ($sequence) => ['value' => 90 + (($sequence->index % 5) * 5)])
            ->create(['read_at' => Carbon::now()->subDays(1)]);

        $result = app(ConsumptionIngestionService::class)->ingest($device, 5000);

        Notification::assertSentTo(
            $user,
            AnomalyDetected::class,
            fn ($notification) => $notification->toArray($user)['device_id'] === $device->id
        );
    }

    public function test_ingest_does_not_notify_when_no_anomaly_is_flagged(): void
    {
        Notification::fake();

        User::factory()->create();
        $device = Device::factory()->create(['status' => 'on']);

        app(ConsumptionIngestionService::class)->ingest($device, 100);

        Notification::assertNothingSent();
    }
}
