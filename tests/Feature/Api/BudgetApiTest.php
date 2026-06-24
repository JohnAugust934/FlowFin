<?php

namespace Tests\Feature\Api;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetApiTest extends TestCase
{
    use RefreshDatabase;

    private function category(?User $user = null): Category
    {
        return Category::factory()->create([
            'user_id' => $user?->id,
            'is_predefined' => $user === null,
        ]);
    }

    public function test_it_creates_a_budget_in_cents(): void
    {
        $user = User::factory()->create();
        $category = $this->category();

        $response = $this->actingAs($user)->postJson('/api/budgets', [
            'category_id' => $category->id,
            'monthly_limit' => 50000,
        ]);

        $response->assertCreated()->assertJsonPath('data.monthly_limit', 50000);
        $this->assertDatabaseHas('budgets', ['user_id' => $user->id, 'category_id' => $category->id, 'monthly_limit' => 50000]);
    }

    public function test_it_rejects_duplicate_budget_for_same_category(): void
    {
        $user = User::factory()->create();
        $category = $this->category();
        Budget::factory()->create(['user_id' => $user->id, 'category_id' => $category->id]);

        $response = $this->actingAs($user)->postJson('/api/budgets', [
            'category_id' => $category->id, 'monthly_limit' => 10000,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['category_id']);
    }

    public function test_it_rejects_a_category_from_another_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $foreign = $this->category($other);

        $response = $this->actingAs($user)->postJson('/api/budgets', [
            'category_id' => $foreign->id, 'monthly_limit' => 10000,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['category_id']);
    }

    public function test_status_marks_ok_below_80_percent(): void
    {
        $user = User::factory()->create();
        $category = $this->category();
        Budget::factory()->create(['user_id' => $user->id, 'category_id' => $category->id, 'monthly_limit' => 10000]);

        Transaction::factory()->saida()->create([
            'user_id' => $user->id, 'category_id' => $category->id, 'amount' => 5000, 'date' => '2026-06-10',
        ]);

        $response = $this->actingAs($user)->getJson('/api/budgets/status?month=2026-06');

        $response->assertOk()
            ->assertJsonPath('0.consumed', 5000)
            ->assertJsonPath('0.percentage', 50)
            ->assertJsonPath('0.status', 'ok');
    }

    public function test_status_marks_alerta_at_80_percent(): void
    {
        $user = User::factory()->create();
        $category = $this->category();
        Budget::factory()->create(['user_id' => $user->id, 'category_id' => $category->id, 'monthly_limit' => 10000]);

        Transaction::factory()->saida()->create([
            'user_id' => $user->id, 'category_id' => $category->id, 'amount' => 8000, 'date' => '2026-06-10',
        ]);

        $response = $this->actingAs($user)->getJson('/api/budgets/status?month=2026-06');

        $response->assertOk()
            ->assertJsonPath('0.percentage', 80)
            ->assertJsonPath('0.status', 'alerta');
    }

    public function test_status_marks_estourado_at_100_percent(): void
    {
        $user = User::factory()->create();
        $category = $this->category();
        Budget::factory()->create(['user_id' => $user->id, 'category_id' => $category->id, 'monthly_limit' => 10000]);

        Transaction::factory()->saida()->create([
            'user_id' => $user->id, 'category_id' => $category->id, 'amount' => 12000, 'date' => '2026-06-10',
        ]);

        $response = $this->actingAs($user)->getJson('/api/budgets/status?month=2026-06');

        $response->assertOk()
            ->assertJsonPath('0.consumed', 12000)
            ->assertJsonPath('0.remaining', -2000)
            ->assertJsonPath('0.percentage', 120)
            ->assertJsonPath('0.status', 'estourado');
    }

    public function test_status_only_counts_outflows_in_the_month_and_category(): void
    {
        $user = User::factory()->create();
        $category = $this->category();
        $otherCategory = $this->category();
        Budget::factory()->create(['user_id' => $user->id, 'category_id' => $category->id, 'monthly_limit' => 10000]);

        Transaction::factory()->saida()->create(['user_id' => $user->id, 'category_id' => $category->id, 'amount' => 3000, 'date' => '2026-06-10']);
        // Fora do mês.
        Transaction::factory()->saida()->create(['user_id' => $user->id, 'category_id' => $category->id, 'amount' => 9999, 'date' => '2026-05-10']);
        // Outra categoria.
        Transaction::factory()->saida()->create(['user_id' => $user->id, 'category_id' => $otherCategory->id, 'amount' => 9999, 'date' => '2026-06-10']);
        // Entrada não conta.
        Transaction::factory()->entrada()->create(['user_id' => $user->id, 'category_id' => $category->id, 'amount' => 9999, 'date' => '2026-06-10']);

        $response = $this->actingAs($user)->getJson('/api/budgets/status?month=2026-06');

        $response->assertOk()->assertJsonPath('0.consumed', 3000);
    }

    public function test_status_is_scoped_to_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Budget::factory()->create(['user_id' => $other->id, 'category_id' => $this->category($other)->id]);

        $response = $this->actingAs($user)->getJson('/api/budgets/status?month=2026-06');

        $response->assertOk()->assertJsonCount(0);
    }

    public function test_it_soft_deletes_a_budget(): void
    {
        $user = User::factory()->create();
        $budget = Budget::factory()->create(['user_id' => $user->id, 'category_id' => $this->category()->id]);

        $this->actingAs($user)->deleteJson("/api/budgets/{$budget->id}")->assertOk();
        $this->assertSoftDeleted('budgets', ['id' => $budget->id]);
    }

    public function test_endpoints_require_authentication(): void
    {
        $this->getJson('/api/budgets/status')->assertUnauthorized();
    }
}
