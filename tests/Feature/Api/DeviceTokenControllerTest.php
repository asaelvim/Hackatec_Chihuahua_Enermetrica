<?php

namespace Tests\Feature\Api;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeviceTokenControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_registers_an_fcm_token_for_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/device-tokens', [
            'fcm_token' => 'token-de-prueba',
            'platform' => 'android',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $user->id,
            'fcm_token' => 'token-de-prueba',
        ]);
    }

    public function test_a_user_can_only_delete_their_own_token(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $token = DeviceToken::factory()->for($owner)->create();

        Sanctum::actingAs($intruder);

        $response = $this->deleteJson("/api/device-tokens/{$token->id}");

        $response->assertForbidden();
    }

    public function test_the_owner_can_delete_their_token(): void
    {
        $owner = User::factory()->create();
        $token = DeviceToken::factory()->for($owner)->create();

        Sanctum::actingAs($owner);

        $response = $this->deleteJson("/api/device-tokens/{$token->id}");

        $response->assertNoContent();
    }
}
