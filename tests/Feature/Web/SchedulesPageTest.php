<?php

namespace Tests\Feature\Web;

use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchedulesPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_for_an_authenticated_user(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        Schedule::factory()->create(['scope' => 'company', 'area_id' => null, 'device_id' => null]);

        $response = $this->actingAs($user)->get('/horarios');

        $response->assertOk()->assertSee('Horarios');
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/horarios')->assertRedirect('/login');
    }
}
