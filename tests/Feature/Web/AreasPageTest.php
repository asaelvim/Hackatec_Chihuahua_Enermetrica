<?php

namespace Tests\Feature\Web;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AreasPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_for_an_authenticated_user(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->get('/areas');

        $response->assertOk()->assertSee('Áreas');
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/areas')->assertRedirect('/login');
    }
}
