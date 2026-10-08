<?php

namespace Tests\Feature\Web;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class DashboardPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_for_an_authenticated_user(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk()->assertSee('Dashboard');
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_it_can_toggle_the_chart_range_between_24h_and_1h(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        $component = Volt::test('pages.dashboard.index');

        $component->assertSet('chartRange', '24h');

        $component->call('setChartRange', '1h');
        $component->assertSet('chartRange', '1h');
        $component->assertDispatched('dashboard-chart-updated');

        $component->call('setChartRange', '24h');
        $component->assertSet('chartRange', '24h');
    }

    public function test_an_invalid_chart_range_falls_back_to_24h(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        $component = Volt::test('pages.dashboard.index');

        $component->call('setChartRange', 'bogus');
        $component->assertSet('chartRange', '24h');
    }
}
