<?php

namespace Tests\Feature\Api;

use App\Models\DeviceModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeviceModelControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_device_models(): void
    {
        Sanctum::actingAs(User::factory()->create());
        DeviceModel::factory()->count(2)->create();

        $response = $this->getJson('/api/device-models');

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_it_creates_a_device_model(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/device-models', ['name' => 'Modelo XYZ-123']);

        $response->assertCreated()->assertJsonPath('data.name', 'Modelo XYZ-123');
    }

    public function test_it_updates_a_device_model(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $deviceModel = DeviceModel::factory()->create();

        $response = $this->putJson("/api/device-models/{$deviceModel->id}", ['name' => 'Modelo ABC-999']);

        $response->assertOk()->assertJsonPath('data.name', 'Modelo ABC-999');
    }

    public function test_it_deletes_a_device_model(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $deviceModel = DeviceModel::factory()->create();

        $response = $this->deleteJson("/api/device-models/{$deviceModel->id}");

        $response->assertNoContent();
    }
}
