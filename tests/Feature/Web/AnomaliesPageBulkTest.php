<?php

namespace Tests\Feature\Web;

use App\Models\Anomaly;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AnomaliesPageBulkTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_unreviewed_filter_works(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        $unreviewed = Anomaly::factory()->create(['reviewed_at' => null]);
        Anomaly::factory()->create(['reviewed_at' => now()]);

        $component = Volt::test('pages.anomalies.index');
        $component->assertSee('Pendiente')->assertSee('Revisada');

        $component->set('onlyUnreviewed', true);
        $component->assertSee('Pendiente')->assertDontSee('Revisada');
    }

    public function test_can_select_multiple_and_mark_as_reviewed(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        $a = Anomaly::factory()->create(['reviewed_at' => null]);
        $b = Anomaly::factory()->create(['reviewed_at' => null]);
        $c = Anomaly::factory()->create(['reviewed_at' => null]);

        $component = Volt::test('pages.anomalies.index');
        $component->set('selected', [$a->id, $b->id]);
        $component->call('markSelectedReviewed');

        $this->assertNotNull($a->fresh()->reviewed_at);
        $this->assertNotNull($b->fresh()->reviewed_at);
        $this->assertNull($c->fresh()->reviewed_at);
        $component->assertSet('selected', []);
    }

    public function test_select_all_selects_unreviewed_rows_on_current_page(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        $unreviewed = Anomaly::factory()->count(3)->create(['reviewed_at' => null]);
        Anomaly::factory()->create(['reviewed_at' => now()]);

        $component = Volt::test('pages.anomalies.index');
        $component->set('selectAll', true);
        $component->call('toggleSelectAll');

        $selected = $component->get('selected');
        $this->assertCount(3, $selected);
        foreach ($unreviewed as $anomaly) {
            $this->assertContains($anomaly->id, $selected);
        }
    }
}
