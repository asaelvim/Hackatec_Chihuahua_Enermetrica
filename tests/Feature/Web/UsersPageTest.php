<?php

namespace Tests\Feature\Web;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class UsersPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_for_an_authenticated_user(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->get('/usuarios');

        $response->assertOk()->assertSee('Usuarios');
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/usuarios')->assertRedirect('/login');
    }

    public function test_an_admin_can_create_another_user(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);

        Volt::actingAs($admin)
            ->test('pages.users.index')
            ->set('name', 'Nuevo Admin')
            ->set('email', 'nuevo@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('save');

        $this->assertDatabaseHas('users', [
            'email' => 'nuevo@example.com',
            'name' => 'Nuevo Admin',
        ]);
    }

    public function test_an_admin_cannot_delete_their_own_account(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);

        Volt::actingAs($admin)
            ->test('pages.users.index')
            ->call('confirmDelete', $admin->id);

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_an_admin_can_delete_another_user(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $other = User::factory()->create();

        Volt::actingAs($admin)
            ->test('pages.users.index')
            ->call('confirmDelete', $other->id)
            ->call('delete');

        $this->assertDatabaseMissing('users', ['id' => $other->id]);
    }

    public function test_register_route_no_longer_exists(): void
    {
        $this->get('/register')->assertNotFound();
    }
}
