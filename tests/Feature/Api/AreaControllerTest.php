<?php

namespace Tests\Feature\Api;

use App\Models\Area;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AreaControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_areas(): void
    {
        Sanctum::actingAs(User::factory()->create());
        Area::factory()->count(3)->create();

        $response = $this->getJson('/api/areas');

        $response->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_it_creates_an_area(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/areas', ['name' => 'Cocina']);

        $response->assertCreated()->assertJsonPath('data.name', 'Cocina');
        $this->assertDatabaseHas('areas', ['name' => 'Cocina']);
    }

    public function test_it_rejects_a_duplicate_area_name(): void
    {
        Sanctum::actingAs(User::factory()->create());
        Area::factory()->create(['name' => 'Cocina']);

        $response = $this->postJson('/api/areas', ['name' => 'Cocina']);

        $response->assertUnprocessable()->assertJsonValidationErrors('name');
    }

    public function test_it_updates_an_area(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $area = Area::factory()->create();

        $response = $this->putJson("/api/areas/{$area->id}", ['name' => 'Garaje']);

        $response->assertOk()->assertJsonPath('data.name', 'Garaje');
    }

    public function test_it_deletes_an_area(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $area = Area::factory()->create();

        $response = $this->deleteJson("/api/areas/{$area->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('areas', ['id' => $area->id]);
    }

    public function test_guests_cannot_access_areas(): void
    {
        $this->getJson('/api/areas')->assertUnauthorized();
    }
}
