<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_the_dashboard_contract_for_the_month(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->predefined()->create();

        Transaction::factory()->create([
            'user_id' => $user->id, 'type' => 'entrada', 'amount' => 50000,
            'category_id' => $category->id, 'date' => '2026-06-10', 'classification' => null,
        ]);
        Transaction::factory()->create([
            'user_id' => $user->id, 'type' => 'saida', 'amount' => 20000,
            'category_id' => $category->id, 'date' => '2026-06-12', 'classification' => 'necessidade',
        ]);

        $response = $this->actingAs($user)->getJson('/api/dashboard?month=2026-06');

        $response->assertOk()
            ->assertJsonPath('month', '2026-06')
            ->assertJsonPath('totals.entrou', 50000)
            ->assertJsonPath('totals.saiu', 20000)
            ->assertJsonPath('totals.sobrou', 30000)
            ->assertJsonStructure([
                'month',
                'totals' => ['entrou', 'saiu', 'sobrou'],
                'by_category',
                'needs_vs_wants' => ['necessidade', 'desejo', 'sem_classificacao', 'necessidade_pct', 'desejo_pct'],
            ]);
    }

    public function test_it_returns_the_six_month_history_series(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->predefined()->create();

        // Mês de referência e dois meses anteriores com movimento.
        Transaction::factory()->create([
            'user_id' => $user->id, 'type' => 'entrada', 'amount' => 30000,
            'category_id' => $category->id, 'date' => '2026-06-05', 'classification' => null,
        ]);
        Transaction::factory()->create([
            'user_id' => $user->id, 'type' => 'saida', 'amount' => 10000,
            'category_id' => $category->id, 'date' => '2026-05-15', 'classification' => 'necessidade',
        ]);
        Transaction::factory()->create([
            'user_id' => $user->id, 'type' => 'entrada', 'amount' => 20000,
            'category_id' => $category->id, 'date' => '2026-04-20', 'classification' => null,
        ]);

        $response = $this->actingAs($user)->getJson('/api/dashboard?month=2026-06');

        $response->assertOk()
            ->assertJsonCount(6, 'history')
            ->assertJsonPath('history.0.month', '2026-01')
            ->assertJsonPath('history.5.month', '2026-06')
            ->assertJsonPath('history.5.entrou', 30000)
            ->assertJsonPath('history.4.saiu', 10000)
            ->assertJsonPath('history.3.entrou', 20000)
            ->assertJsonPath('history.3.sobrou', 20000);
    }

    public function test_history_reflects_changes_after_cache_invalidation(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->predefined()->create();

        // Primeiro acesso aquece o cache com o mês anterior zerado.
        $this->actingAs($user)->getJson('/api/dashboard?month=2026-06')
            ->assertOk()
            ->assertJsonPath('history.4.entrou', 0);

        // Nova transação no mês anterior deve invalidar o cache dos totais dele.
        Transaction::factory()->create([
            'user_id' => $user->id, 'type' => 'entrada', 'amount' => 5000,
            'category_id' => $category->id, 'date' => '2026-05-10', 'classification' => null,
        ]);

        $this->actingAs($user)->getJson('/api/dashboard?month=2026-06')
            ->assertOk()
            ->assertJsonPath('history.4.entrou', 5000);
    }

    public function test_it_defaults_to_the_current_month(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->predefined()->create();

        Transaction::factory()->create([
            'user_id' => $user->id, 'type' => 'entrada', 'amount' => 12345,
            'category_id' => $category->id, 'date' => now()->format('Y-m-d'), 'classification' => null,
        ]);

        $this->actingAs($user)->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('month', now()->format('Y-m'))
            ->assertJsonPath('totals.entrou', 12345);
    }

    public function test_it_rejects_invalid_month_format(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/dashboard?month=junho')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['month']);
    }

    public function test_it_is_scoped_to_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $category = Category::factory()->predefined()->create();

        Transaction::factory()->create([
            'user_id' => $other->id, 'type' => 'entrada', 'amount' => 999999,
            'category_id' => $category->id, 'date' => '2026-06-10', 'classification' => null,
        ]);

        $this->actingAs($user)->getJson('/api/dashboard?month=2026-06')
            ->assertOk()
            ->assertJsonPath('totals.entrou', 0);
    }

    public function test_it_requires_authentication(): void
    {
        $this->getJson('/api/dashboard')->assertUnauthorized();
    }
}
