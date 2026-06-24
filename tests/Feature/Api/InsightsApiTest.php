<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Recurrence;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InsightsApiTest extends TestCase
{
    use RefreshDatabase;

    private function category(?User $user = null): Category
    {
        return Category::factory()->create([
            'user_id' => $user?->id,
            'is_predefined' => $user === null,
        ]);
    }

    private function saida(User $user, Category $category, int $amount, string $date): Transaction
    {
        return Transaction::factory()->saida()->create([
            'user_id' => $user->id, 'category_id' => $category->id, 'amount' => $amount, 'date' => $date,
        ]);
    }

    public function test_top_expenses_returns_three_largest_outflows(): void
    {
        $user = User::factory()->create();
        $category = $this->category();

        $this->saida($user, $category, 1000, '2026-06-02');
        $maior = $this->saida($user, $category, 9000, '2026-06-03');
        $medio = $this->saida($user, $category, 5000, '2026-06-04');
        $terceiro = $this->saida($user, $category, 3000, '2026-06-05');
        // Entrada não conta como gasto.
        Transaction::factory()->entrada()->create([
            'user_id' => $user->id, 'category_id' => $category->id, 'amount' => 99999, 'date' => '2026-06-06',
        ]);

        $response = $this->actingAs($user)->getJson('/api/insights?month=2026-06');

        $response->assertOk()->assertJsonCount(3, 'top_expenses');
        $ids = collect($response->json('top_expenses'))->pluck('id')->all();
        $this->assertSame([$maior->id, $medio->id, $terceiro->id], $ids);
        $this->assertSame(9000, $response->json('top_expenses.0.amount'));
    }

    public function test_daily_timeline_covers_every_day_and_sums_outflows(): void
    {
        $user = User::factory()->create();
        $category = $this->category();

        $this->saida($user, $category, 1000, '2026-06-01');
        $this->saida($user, $category, 2000, '2026-06-01'); // mesmo dia soma
        $this->saida($user, $category, 5000, '2026-06-15');

        $response = $this->actingAs($user)->getJson('/api/insights?month=2026-06');

        $response->assertOk()->assertJsonCount(30, 'daily_timeline'); // junho tem 30 dias

        $timeline = collect($response->json('daily_timeline'))->keyBy('date');
        $this->assertSame(3000, $timeline['2026-06-01']['total']);
        $this->assertSame(5000, $timeline['2026-06-15']['total']);
        $this->assertSame(0, $timeline['2026-06-02']['total']);
    }

    public function test_month_comparison_computes_variation_percentage(): void
    {
        $user = User::factory()->create();
        $category = $this->category();

        // Mês anterior (maio): saiu 10000, entrou 20000.
        $this->saida($user, $category, 10000, '2026-05-10');
        Transaction::factory()->entrada()->create([
            'user_id' => $user->id, 'category_id' => $category->id, 'amount' => 20000, 'date' => '2026-05-10',
        ]);
        // Mês atual (junho): saiu 15000, entrou 20000.
        $this->saida($user, $category, 15000, '2026-06-10');
        Transaction::factory()->entrada()->create([
            'user_id' => $user->id, 'category_id' => $category->id, 'amount' => 20000, 'date' => '2026-06-10',
        ]);

        $response = $this->actingAs($user)->getJson('/api/insights?month=2026-06');

        $response->assertOk()
            ->assertJsonPath('month_comparison.current.saiu', 15000)
            ->assertJsonPath('month_comparison.previous.month', '2026-05')
            ->assertJsonPath('month_comparison.previous.saiu', 10000)
            ->assertJsonPath('month_comparison.variation.saiu_pct', 50)   // 10000→15000 = +50%
            ->assertJsonPath('month_comparison.variation.entrou_pct', 0); // sem variação
    }

    public function test_month_comparison_variation_is_null_without_baseline(): void
    {
        $user = User::factory()->create();
        $category = $this->category();

        // Apenas mês atual; mês anterior zerado → variação null.
        $this->saida($user, $category, 5000, '2026-06-10');

        $response = $this->actingAs($user)->getJson('/api/insights?month=2026-06');

        $response->assertOk()->assertJsonPath('month_comparison.variation.saiu_pct', null);
    }

    public function test_insights_are_scoped_to_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->saida($other, $this->category(), 80000, '2026-06-10');

        $response = $this->actingAs($user)->getJson('/api/insights?month=2026-06');

        $response->assertOk()
            ->assertJsonCount(0, 'top_expenses')
            ->assertJsonPath('month_comparison.current.saiu', 0);
    }

    public function test_insights_cache_is_invalidated_when_a_transaction_is_added(): void
    {
        $user = User::factory()->create();
        $category = $this->category();

        $this->saida($user, $category, 1000, '2026-06-10');
        // Primeira leitura: popula o cache.
        $this->actingAs($user)->getJson('/api/insights?month=2026-06')
            ->assertJsonPath('month_comparison.current.saiu', 1000);

        // Nova transação invalida o cache do mês.
        $this->saida($user, $category, 4000, '2026-06-11');

        $this->actingAs($user)->getJson('/api/insights?month=2026-06')
            ->assertJsonPath('month_comparison.current.saiu', 5000);
    }

    public function test_invisible_spending_groups_active_outflow_recurrences(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $category = $this->category();

        // Mensal R$ 30,00 → impacto 3000.
        Recurrence::factory()->saida()->create([
            'user_id' => $user->id, 'category_id' => $category->id,
            'description' => 'Streaming', 'amount' => 3000, 'frequency' => 'mensal', 'start_date' => '2026-01-01',
        ]);
        // Semanal R$ 10,00 → impacto round(1000*52/12)=4333.
        Recurrence::factory()->saida()->create([
            'user_id' => $user->id, 'category_id' => $category->id,
            'description' => 'App', 'amount' => 1000, 'frequency' => 'semanal', 'start_date' => '2026-01-01',
        ]);
        // Entrada e inativa não contam.
        Recurrence::factory()->entrada()->create(['user_id' => $user->id, 'category_id' => $category->id, 'amount' => 50000]);
        Recurrence::factory()->inactive()->saida()->create(['user_id' => $user->id, 'category_id' => $category->id, 'amount' => 99999]);
        // De outro usuário não conta.
        Recurrence::factory()->saida()->create(['user_id' => $other->id, 'category_id' => $this->category()->id, 'amount' => 12345]);

        $response = $this->actingAs($user)->getJson('/api/insights/invisible');

        $response->assertOk()
            ->assertJsonPath('count', 2)
            ->assertJsonPath('total_monthly_impact', 3000 + 4333);
    }

    public function test_endpoints_require_authentication(): void
    {
        $this->getJson('/api/insights')->assertUnauthorized();
        $this->getJson('/api/insights/invisible')->assertUnauthorized();
    }
}
