<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_the_24h_chart_by_default(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/dashboard');

        $response->assertOk();
        $this->assertCount(23, $response->json('chartData.labels'));
    }

    public function test_it_returns_a_60_minute_chart_when_range_is_1h(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/dashboard?range=1h');

        $response->assertOk();
        $this->assertCount(59, $response->json('chartData.labels'));
    }
}
