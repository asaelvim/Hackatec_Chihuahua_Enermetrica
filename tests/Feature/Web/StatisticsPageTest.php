<?php

namespace Tests\Feature\Web;

use App\Models\ConsumptionReading;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatisticsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_for_an_authenticated_user(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        ConsumptionReading::factory()->create();

        $response = $this->actingAs($user)->get('/estadisticas');

        $response->assertOk()->assertSee('Estadísticas');
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/estadisticas')->assertRedirect('/login');
    }
}
