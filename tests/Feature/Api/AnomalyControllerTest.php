<?php

namespace Tests\Feature\Api;

use App\Models\Anomaly;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AnomalyControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_anomalies(): void
    {
        Sanctum::actingAs(User::factory()->create());
        Anomaly::factory()->count(2)->create();

        $response = $this->getJson('/api/anomalies');

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_it_filters_unreviewed_anomalies(): void
    {
        Sanctum::actingAs(User::factory()->create());
        Anomaly::factory()->create(['reviewed_at' => now()]);
        Anomaly::factory()->create(['reviewed_at' => null]);

        $response = $this->getJson('/api/anomalies?only_unreviewed=1');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_it_marks_an_anomaly_as_reviewed(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $anomaly = Anomaly::factory()->create(['reviewed_at' => null]);

        $response = $this->postJson("/api/anomalies/{$anomaly->id}/review");

        $response->assertOk()->assertJsonPath('data.reviewed_at', fn ($value) => $value !== null);
        $this->assertNotNull($anomaly->fresh()->reviewed_at);
    }
}
