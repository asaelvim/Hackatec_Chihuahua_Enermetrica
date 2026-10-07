<?php

namespace Tests\Feature\Web;

use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DevicesPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_for_an_authenticated_user(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        Device::factory()->create();

        $response = $this->actingAs($user)->get('/dispositivos');

        $response->assertOk()->assertSee('Dispositivos');
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dispositivos')->assertRedirect('/login');
    }
}
