<?php

namespace Tests\Feature\Api;

use App\Models\Area;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ScheduleControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_schedules(): void
    {
        Sanctum::actingAs(User::factory()->create());
        Schedule::factory()->count(2)->create();

        $response = $this->getJson('/api/schedules');

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_it_creates_a_company_wide_schedule(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/schedules', [
            'name' => 'Apagado nocturno',
            'scope' => 'company',
            'start_time' => '22:00:00',
            'end_time' => '06:00:00',
            'weekdays' => '0,1,2,3,4,5,6',
            'is_active' => true,
        ]);

        $response->assertCreated()->assertJsonPath('data.scope', 'company');
    }

    public function test_an_area_schedule_requires_an_area_id(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/schedules', [
            'name' => 'Apagado de área',
            'scope' => 'area',
            'start_time' => '22:00:00',
            'weekdays' => '0,1,2,3,4,5,6',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('area_id');
    }

    public function test_it_creates_an_area_schedule_with_a_valid_area(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $area = Area::factory()->create();

        $response = $this->postJson('/api/schedules', [
            'name' => 'Apagado de área',
            'scope' => 'area',
            'area_id' => $area->id,
            'start_time' => '22:00:00',
            'weekdays' => '0,1,2,3,4,5,6',
        ]);

        $response->assertCreated()->assertJsonPath('data.area.id', $area->id);
    }

    public function test_it_updates_a_schedule(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $schedule = Schedule::factory()->create();

        $response = $this->putJson("/api/schedules/{$schedule->id}", ['is_active' => false]);

        $response->assertOk()->assertJsonPath('data.is_active', false);
    }

    public function test_it_deletes_a_schedule(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $schedule = Schedule::factory()->create();

        $response = $this->deleteJson("/api/schedules/{$schedule->id}");

        $response->assertNoContent();
    }
}
