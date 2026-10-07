<?php

namespace Tests\Feature\Api;

use App\Models\DeviceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeviceTypeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_device_types(): void
    {
        Sanctum::actingAs(User::factory()->create());
        DeviceType::factory()->count(2)->create();

        $response = $this->getJson('/api/device-types');

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_it_creates_a_device_type(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/device-types', ['name' => 'Foco']);

        $response->assertCreated()->assertJsonPath('data.name', 'Foco');
    }

    public function test_it_updates_a_device_type(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $deviceType = DeviceType::factory()->create();

        $response = $this->putJson("/api/device-types/{$deviceType->id}", ['name' => 'Microondas']);

        $response->assertOk()->assertJsonPath('data.name', 'Microondas');
    }

    public function test_it_deletes_a_device_type(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $deviceType = DeviceType::factory()->create();

        $response = $this->deleteJson("/api/device-types/{$deviceType->id}");

        $response->assertNoContent();
    }
}
