<?php

namespace Tests\Feature\Api;

use App\Jobs\RecalculateStreaks;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StreakApiTest extends TestCase
{
    use RefreshDatabase;

    private function category(): Category
    {
        return Category::factory()->create(['user_id' => null, 'is_predefined' => true]);
    }

    private function tx(User $user, Category $category, string $date): Transaction
    {
        return Transaction::factory()->saida()->create([
            'user_id' => $user->id, 'category_id' => $category->id, 'amount' => 1000, 'date' => $date,
        ]);
    }

    public function test_streak_counts_consecutive_days_including_today(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-06-15 10:00:00'));
        $user = User::factory()->create();
        $cat = $this->category();

        $this->tx($user, $cat, '2026-06-13');
        $this->tx($user, $cat, '2026-06-14');
        $this->tx($user, $cat, '2026-06-15');

        $this->actingAs($user)->getJson('/api/streak')
            ->assertOk()
            ->assertJsonPath('current_streak', 3)
            ->assertJsonPath('active', true)
            ->assertJsonPath('last_activity_date', '2026-06-15');
    }

    public function test_streak_uses_yesterday_when_today_has_no_record(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-06-15 10:00:00'));
        $user = User::factory()->create();
        $cat = $this->category();

        // Sem registro hoje; ontem e anteontem têm → sequência ainda válida (dia em aberto).
        $this->tx($user, $cat, '2026-06-13');
        $this->tx($user, $cat, '2026-06-14');

        $this->actingAs($user)->getJson('/api/streak')
            ->assertOk()
            ->assertJsonPath('current_streak', 2)
            ->assertJsonPath('active', true);
    }

    public function test_streak_breaks_when_a_day_is_skipped(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-06-15 10:00:00'));
        $user = User::factory()->create();
        $cat = $this->category();

        // Pulou o dia 14: só hoje conta.
        $this->tx($user, $cat, '2026-06-12');
        $this->tx($user, $cat, '2026-06-15');

        $this->actingAs($user)->getJson('/api/streak')
            ->assertOk()
            ->assertJsonPath('current_streak', 1);
    }

    public function test_streak_is_zero_without_recent_activity(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-06-15 10:00:00'));
        $user = User::factory()->create();
        $cat = $this->category();

        $this->tx($user, $cat, '2026-06-10');

        $this->actingAs($user)->getJson('/api/streak')
            ->assertOk()
            ->assertJsonPath('current_streak', 0)
            ->assertJsonPath('active', false)
            ->assertJsonPath('last_activity_date', '2026-06-10');
    }

    public function test_scheduled_job_persists_streak_snapshot(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-06-15 10:00:00'));
        $user = User::factory()->create(['current_streak' => 0]);
        $cat = $this->category();

        $this->tx($user, $cat, '2026-06-13');
        $this->tx($user, $cat, '2026-06-14');
        $this->tx($user, $cat, '2026-06-15');

        // Fila síncrona nos testes (no ambiente real: fila no banco via scheduler).
        RecalculateStreaks::dispatch();

        $this->assertSame(3, $user->fresh()->current_streak);
    }

    public function test_streak_job_is_registered_in_the_scheduler(): void
    {
        $events = app(Schedule::class)->events();

        $registered = collect($events)->contains(
            fn ($event) => str_contains($event->getSummaryForDisplay(), 'RecalculateStreaks'),
        );

        $this->assertTrue($registered, 'O job RecalculateStreaks deveria estar agendado no scheduler.');
    }

    public function test_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/streak')->assertUnauthorized();
    }
}
