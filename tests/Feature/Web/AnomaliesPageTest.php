<?php

namespace Tests\Feature\Web;

use App\Models\Anomaly;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnomaliesPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_for_an_authenticated_user(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        Anomaly::factory()->create();

        $response = $this->actingAs($user)->get('/anomalias');

        $response->assertOk()->assertSee('Anomalías');
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/anomalias')->assertRedirect('/login');
    }
}
