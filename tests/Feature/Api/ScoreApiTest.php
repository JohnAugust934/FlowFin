<?php

namespace Tests\Feature\Api;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScoreApiTest extends TestCase
{
    use RefreshDatabase;

    private function category(?string $name = null): Category
    {
        return Category::factory()->create([
            'user_id' => null,
            'is_predefined' => true,
            'name' => $name ?? fake()->unique()->word(),
        ]);
    }

    private function tx(User $user, Category $category, string $type, int $amount, string $date, ?string $classification = null): Transaction
    {
        return Transaction::factory()->create([
            'user_id' => $user->id, 'category_id' => $category->id,
            'type' => $type, 'amount' => $amount, 'date' => $date, 'classification' => $classification,
        ]);
    }

    public function test_score_with_only_consistency_renormalizes_to_that_factor(): void
    {
        // Sem orçamento e sem meta: o Score é apenas a consistência.
        $user = User::factory()->create(['monthly_savings_goal' => null]);
        $cat = $this->category();

        // Junho tem 30 dias; registros em 15 dias distintos → consistência 50.
        for ($d = 1; $d <= 15; $d++) {
            $this->tx($user, $cat, 'saida', 1000, sprintf('2026-06-%02d', $d), 'desejo');
        }

        $this->actingAs($user)->getJson('/api/score?month=2026-06')
            ->assertOk()
            ->assertJsonPath('score', 50)
            ->assertJsonPath('factors.consistency.value', 50)
            ->assertJsonPath('factors.consistency.included', true)
            ->assertJsonPath('factors.budgets.included', false)
            ->assertJsonPath('factors.savings_goal.included', false);
    }

    public function test_score_renormalizes_consistency_and_budgets_without_goal(): void
    {
        $user = User::factory()->create(['monthly_savings_goal' => null]);
        $noBudget = $this->category();
        $catA = $this->category();
        $catB = $this->category();

        Budget::factory()->create(['user_id' => $user->id, 'category_id' => $catA->id, 'monthly_limit' => 10000]);
        Budget::factory()->create(['user_id' => $user->id, 'category_id' => $catB->id, 'monthly_limit' => 10000]);

        // Ambos os orçamentos respeitados (consumo ≤ limite) → fator orçamentos 100.
        $this->tx($user, $catA, 'saida', 5000, '2026-06-01', 'necessidade');
        $this->tx($user, $catB, 'saida', 5000, '2026-06-02', 'necessidade');

        // Mais 13 dias distintos (03..15) → total 15 dias → consistência 50.
        for ($d = 3; $d <= 15; $d++) {
            $this->tx($user, $noBudget, 'saida', 1000, sprintf('2026-06-%02d', $d), 'desejo');
        }

        // (50*40 + 100*30) / 70 = 71,43 → 71.
        $this->actingAs($user)->getJson('/api/score?month=2026-06')
            ->assertOk()
            ->assertJsonPath('score', 71)
            ->assertJsonPath('factors.consistency.value', 50)
            ->assertJsonPath('factors.budgets.value', 100)
            ->assertJsonPath('factors.budgets.included', true)
            ->assertJsonPath('factors.savings_goal.included', false);
    }

    public function test_score_uses_all_three_factors_with_goal_capped_at_100(): void
    {
        $user = User::factory()->create(['monthly_savings_goal' => 20000]);
        $noBudget = $this->category();
        $catA = $this->category();

        Budget::factory()->create(['user_id' => $user->id, 'category_id' => $catA->id, 'monthly_limit' => 10000]);
        $this->tx($user, $catA, 'saida', 5000, '2026-06-01', 'necessidade'); // respeitado

        // Entrada grande → "sobrou" supera a meta → progresso capado em 100.
        $this->tx($user, $noBudget, 'entrada', 100000, '2026-06-02');

        // Dias 03..15 com pequenas saídas → total 15 dias distintos → consistência 50.
        for ($d = 3; $d <= 15; $d++) {
            $this->tx($user, $noBudget, 'saida', 100, sprintf('2026-06-%02d', $d), 'desejo');
        }

        // (50*40 + 100*30 + 100*30) / 100 = 80.
        $this->actingAs($user)->getJson('/api/score?month=2026-06')
            ->assertOk()
            ->assertJsonPath('score', 80)
            ->assertJsonPath('factors.savings_goal.value', 100)
            ->assertJsonPath('factors.savings_goal.included', true);
    }

    public function test_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/score')->assertUnauthorized();
    }
}
