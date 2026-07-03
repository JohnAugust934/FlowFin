<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Recurrence;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavingsReportApiTest extends TestCase
{
    use RefreshDatabase;

    private function category(?User $user = null, ?string $name = null): Category
    {
        return Category::factory()->create([
            'user_id' => $user?->id,
            'is_predefined' => $user === null,
            'name' => $name ?? fake()->unique()->word(),
        ]);
    }

    private function desejo(User $user, Category $category, int $amount, string $date): Transaction
    {
        return Transaction::factory()->create([
            'user_id' => $user->id, 'category_id' => $category->id,
            'type' => 'saida', 'classification' => 'desejo', 'amount' => $amount, 'date' => $date,
        ]);
    }

    public function test_it_suggests_30_percent_cut_for_desejo_categories(): void
    {
        $user = User::factory()->create();
        $lazer = $this->category(name: 'Lazer');

        // Lazer (desejo): 30000 → corte 30% → economia 9000.
        $this->desejo($user, $lazer, 20000, '2026-06-05');
        $this->desejo($user, $lazer, 10000, '2026-06-06');

        $response = $this->actingAs($user)->getJson('/api/savings-report?month=2026-06');

        $response->assertOk()
            ->assertJsonCount(1, 'suggestions')
            ->assertJsonPath('suggestions.0.type', 'categoria_desejo')
            ->assertJsonPath('suggestions.0.label', 'Lazer')
            ->assertJsonPath('suggestions.0.current_amount', 30000)
            ->assertJsonPath('suggestions.0.cut_pct', 30)
            ->assertJsonPath('suggestions.0.estimated_savings', 9000)
            ->assertJsonPath('total_potential_savings', 9000);
    }

    public function test_it_ignores_necessidade_and_unclassified_outflows_and_inflows(): void
    {
        $user = User::factory()->create();
        $category = $this->category(name: 'Alimentação');

        $this->desejo($user, $category, 5000, '2026-06-05');
        // Necessidade não entra.
        Transaction::factory()->create(['user_id' => $user->id, 'category_id' => $category->id, 'type' => 'saida', 'classification' => 'necessidade', 'amount' => 99999, 'date' => '2026-06-06']);
        // Sem classificação não entra.
        Transaction::factory()->create(['user_id' => $user->id, 'category_id' => $category->id, 'type' => 'saida', 'classification' => null, 'amount' => 88888, 'date' => '2026-06-07']);
        // Entrada não entra.
        Transaction::factory()->entrada()->create(['user_id' => $user->id, 'category_id' => $category->id, 'amount' => 77777, 'date' => '2026-06-08']);

        $response = $this->actingAs($user)->getJson('/api/savings-report?month=2026-06');

        $response->assertOk()
            ->assertJsonCount(1, 'suggestions')
            ->assertJsonPath('suggestions.0.current_amount', 5000)
            ->assertJsonPath('suggestions.0.estimated_savings', 1500);
    }

    public function test_it_includes_recurrences_with_20_percent_cut(): void
    {
        $user = User::factory()->create();
        $category = $this->category(name: 'Assinaturas');

        // Recorrente mensal 10000 → corte 20% → economia 2000.
        Recurrence::factory()->saida()->create([
            'user_id' => $user->id, 'category_id' => $category->id,
            'description' => 'Streaming', 'amount' => 10000, 'frequency' => 'mensal', 'start_date' => '2026-01-01',
        ]);
        // Inativa e entrada não entram.
        Recurrence::factory()->inactive()->saida()->create(['user_id' => $user->id, 'category_id' => $category->id, 'amount' => 99999]);
        Recurrence::factory()->entrada()->create(['user_id' => $user->id, 'category_id' => $category->id, 'amount' => 88888]);

        $response = $this->actingAs($user)->getJson('/api/savings-report?month=2026-06');

        $response->assertOk()
            ->assertJsonCount(1, 'suggestions')
            ->assertJsonPath('suggestions.0.type', 'recorrente')
            ->assertJsonPath('suggestions.0.label', 'Streaming')
            ->assertJsonPath('suggestions.0.current_amount', 10000)
            ->assertJsonPath('suggestions.0.cut_pct', 20)
            ->assertJsonPath('suggestions.0.estimated_savings', 2000);
    }

    public function test_suggestions_are_ordered_by_potential_savings_desc(): void
    {
        $user = User::factory()->create();
        $lazer = $this->category(name: 'Lazer');
        $compras = $this->category(name: 'Compras');

        // Desejo Compras: 50000 → economia 15000 (maior).
        $this->desejo($user, $compras, 50000, '2026-06-05');
        // Desejo Lazer: 20000 → economia 6000.
        $this->desejo($user, $lazer, 20000, '2026-06-05');
        // Recorrente: 10000 → economia 2000 (menor).
        Recurrence::factory()->saida()->create([
            'user_id' => $user->id, 'category_id' => $this->category(name: 'Assinaturas')->id,
            'description' => 'Streaming', 'amount' => 10000, 'frequency' => 'mensal', 'start_date' => '2026-01-01',
        ]);

        $response = $this->actingAs($user)->getJson('/api/savings-report?month=2026-06');

        $response->assertOk()->assertJsonCount(3, 'suggestions');
        $savings = collect($response->json('suggestions'))->pluck('estimated_savings')->all();
        $this->assertSame([15000, 6000, 2000], $savings);
        $this->assertSame(23000, $response->json('total_potential_savings'));
    }

    public function test_report_is_scoped_to_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->desejo($other, $this->category(name: 'Lazer'), 50000, '2026-06-05');
        Recurrence::factory()->saida()->create(['user_id' => $other->id, 'category_id' => $this->category(name: 'X')->id, 'amount' => 9999]);

        $response = $this->actingAs($user)->getJson('/api/savings-report?month=2026-06');

        $response->assertOk()
            ->assertJsonCount(0, 'suggestions')
            ->assertJsonPath('total_potential_savings', 0);
    }

    public function test_cache_is_invalidated_when_a_transaction_is_added(): void
    {
        $user = User::factory()->create();
        $lazer = $this->category(name: 'Lazer');

        $this->desejo($user, $lazer, 10000, '2026-06-05');
        $this->actingAs($user)->getJson('/api/savings-report?month=2026-06')
            ->assertJsonPath('total_potential_savings', 3000);

        // Nova saída de desejo invalida o cache do mês.
        $this->desejo($user, $lazer, 10000, '2026-06-06');

        $this->actingAs($user)->getJson('/api/savings-report?month=2026-06')
            ->assertJsonPath('total_potential_savings', 6000);
    }

    public function test_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/savings-report')->assertUnauthorized();
    }
}
