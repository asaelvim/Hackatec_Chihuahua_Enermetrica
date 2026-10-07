<?php

namespace Tests\Feature\Web;

use App\Models\DeviceModel;
use App\Models\DeviceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_for_an_authenticated_user(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        DeviceType::factory()->create();
        DeviceModel::factory()->create();

        $response = $this->actingAs($user)->get('/catalogos');

        $response->assertOk()->assertSee('Catálogos');
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/catalogos')->assertRedirect('/login');
    }
}
