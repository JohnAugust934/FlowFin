<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionApiTest extends TestCase
{
    use RefreshDatabase;

    private function category(?User $user = null): Category
    {
        return Category::factory()->create([
            'user_id' => $user?->id,
            'is_predefined' => $user === null,
        ]);
    }

    public function test_it_lists_only_the_authenticated_users_transactions_paginated(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Transaction::factory()->count(25)->create([
            'user_id' => $user->id,
            'category_id' => $this->category()->id,
        ]);
        Transaction::factory()->count(3)->create([
            'user_id' => $other->id,
            'category_id' => $this->category()->id,
        ]);

        $response = $this->actingAs($user)->getJson('/api/transactions');

        $response->assertOk()
            ->assertJsonCount(20, 'data')          // pagina em 20
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('meta.total', 25);    // só as 25 do usuário
    }

    public function test_it_creates_a_transaction_storing_amount_in_cents(): void
    {
        $user = User::factory()->create();
        $category = $this->category();

        $response = $this->actingAs($user)->postJson('/api/transactions', [
            'type' => 'saida',
            'amount' => 123456,                     // centavos
            'category_id' => $category->id,
            'date' => '2026-06-20',
            'description' => 'Mercado',
            'classification' => 'necessidade',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.amount', 123456)
            ->assertJsonPath('data.type', 'saida');

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'amount' => 123456,
            'description' => 'Mercado',
        ]);
    }

    public function test_it_accepts_formatted_currency_and_converts_to_cents(): void
    {
        $user = User::factory()->create();
        $category = $this->category();

        $response = $this->actingAs($user)->postJson('/api/transactions', [
            'type' => 'entrada',
            'amount' => 'R$ 1.234,56',
            'category_id' => $category->id,
            'date' => '2026-06-20',
        ]);

        $response->assertCreated()->assertJsonPath('data.amount', 123456);
    }

    public function test_it_validates_required_fields_with_422_json(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/transactions', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type', 'amount', 'category_id', 'date']);
    }

    public function test_it_rejects_a_category_from_another_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $foreignCategory = $this->category($other);

        $response = $this->actingAs($user)->postJson('/api/transactions', [
            'type' => 'saida',
            'amount' => 1000,
            'category_id' => $foreignCategory->id,
            'date' => '2026-06-20',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['category_id']);
    }

    public function test_it_updates_a_transaction(): void
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'category_id' => $this->category()->id,
            'amount' => 1000,
        ]);

        $response = $this->actingAs($user)->putJson("/api/transactions/{$transaction->id}", [
            'type' => 'entrada',
            'amount' => 5000,
            'category_id' => $transaction->category_id,
            'date' => '2026-06-21',
        ]);

        $response->assertOk()->assertJsonPath('data.amount', 5000);
        $this->assertSame(5000, $transaction->refresh()->amount);
    }

    public function test_it_soft_deletes_a_transaction(): void
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'category_id' => $this->category()->id,
        ]);

        $response = $this->actingAs($user)->deleteJson("/api/transactions/{$transaction->id}");

        $response->assertOk();
        $this->assertSoftDeleted('transactions', ['id' => $transaction->id]);
    }

    public function test_a_user_cannot_access_another_users_transaction(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $foreign = Transaction::factory()->create([
            'user_id' => $other->id,
            'category_id' => $this->category()->id,
        ]);

        $this->actingAs($user)->getJson("/api/transactions/{$foreign->id}")->assertNotFound();
        $this->actingAs($user)->putJson("/api/transactions/{$foreign->id}", [
            'type' => 'entrada', 'amount' => 100, 'category_id' => $foreign->category_id, 'date' => '2026-06-20',
        ])->assertNotFound();
        $this->actingAs($user)->deleteJson("/api/transactions/{$foreign->id}")->assertNotFound();

        // E permanece intacta.
        $this->assertDatabaseHas('transactions', ['id' => $foreign->id, 'deleted_at' => null]);
    }

    public function test_endpoints_require_authentication(): void
    {
        $this->getJson('/api/transactions')->assertUnauthorized();
    }

    public function test_it_filters_by_date_range_inclusive(): void
    {
        $user = User::factory()->create();
        $category = $this->category();

        $inicio = Transaction::factory()->create([
            'user_id' => $user->id, 'category_id' => $category->id, 'date' => '2026-03-01',
        ]);
        $meio = Transaction::factory()->create([
            'user_id' => $user->id, 'category_id' => $category->id, 'date' => '2026-03-15',
        ]);
        $fim = Transaction::factory()->create([
            'user_id' => $user->id, 'category_id' => $category->id, 'date' => '2026-03-31',
        ]);
        // Fora do intervalo.
        Transaction::factory()->create([
            'user_id' => $user->id, 'category_id' => $category->id, 'date' => '2026-02-28',
        ]);
        Transaction::factory()->create([
            'user_id' => $user->id, 'category_id' => $category->id, 'date' => '2026-04-01',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/transactions?date_from=2026-03-01&date_to=2026-03-31');

        $response->assertOk()->assertJsonPath('meta.total', 3);
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$inicio->id, $meio->id, $fim->id], $ids);
    }

    public function test_it_filters_by_category(): void
    {
        $user = User::factory()->create();
        $catA = $this->category();
        $catB = $this->category();

        Transaction::factory()->count(2)->create([
            'user_id' => $user->id, 'category_id' => $catA->id,
        ]);
        Transaction::factory()->count(3)->create([
            'user_id' => $user->id, 'category_id' => $catB->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/transactions?category_id={$catA->id}");

        $response->assertOk()->assertJsonPath('meta.total', 2);
    }

    public function test_it_filters_by_type(): void
    {
        $user = User::factory()->create();
        $category = $this->category();

        Transaction::factory()->count(2)->entrada()->create([
            'user_id' => $user->id, 'category_id' => $category->id,
        ]);
        Transaction::factory()->count(4)->saida()->create([
            'user_id' => $user->id, 'category_id' => $category->id,
        ]);

        $response = $this->actingAs($user)->getJson('/api/transactions?type=entrada');

        $response->assertOk()->assertJsonPath('meta.total', 2);
        foreach ($response->json('data') as $t) {
            $this->assertSame('entrada', $t['type']);
        }
    }

    public function test_it_combines_filters(): void
    {
        $user = User::factory()->create();
        $category = $this->category();

        // Alvo: entrada, na categoria, dentro do período.
        $alvo = Transaction::factory()->entrada()->create([
            'user_id' => $user->id, 'category_id' => $category->id, 'date' => '2026-05-10',
        ]);
        // Ruídos que falham em ao menos um critério.
        Transaction::factory()->saida()->create([
            'user_id' => $user->id, 'category_id' => $category->id, 'date' => '2026-05-10',
        ]);
        Transaction::factory()->entrada()->create([
            'user_id' => $user->id, 'category_id' => $this->category()->id, 'date' => '2026-05-10',
        ]);
        Transaction::factory()->entrada()->create([
            'user_id' => $user->id, 'category_id' => $category->id, 'date' => '2026-01-01',
        ]);

        $response = $this->actingAs($user)->getJson(
            "/api/transactions?type=entrada&category_id={$category->id}&date_from=2026-05-01&date_to=2026-05-31"
        );

        $response->assertOk()->assertJsonPath('meta.total', 1);
        $this->assertSame($alvo->id, $response->json('data.0.id'));
    }

    public function test_filtered_results_remain_paginated(): void
    {
        $user = User::factory()->create();
        $category = $this->category();

        Transaction::factory()->count(25)->saida()->create([
            'user_id' => $user->id, 'category_id' => $category->id,
        ]);
        Transaction::factory()->count(10)->entrada()->create([
            'user_id' => $user->id, 'category_id' => $category->id,
        ]);

        $response = $this->actingAs($user)->getJson('/api/transactions?type=saida');

        $response->assertOk()
            ->assertJsonCount(20, 'data')          // ainda 20/página
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('meta.total', 25);    // total reflete só o filtrado
    }

    public function test_filtering_by_another_users_category_does_not_leak_data(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $foreignCategory = $this->category($other);

        Transaction::factory()->count(3)->create([
            'user_id' => $other->id, 'category_id' => $foreignCategory->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/transactions?category_id={$foreignCategory->id}");

        // A categoria de outro usuário é inválida → 422, sem vazar dados nem existência.
        $response->assertStatus(422)->assertJsonValidationErrors(['category_id']);
    }

    public function test_it_validates_filter_params_with_422(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(
            '/api/transactions?date_from=nao-e-data&type=invalido&date_to=2020-01-01'
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date_from', 'type']);
    }

    public function test_it_rejects_date_to_before_date_from(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(
            '/api/transactions?date_from=2026-03-31&date_to=2026-03-01'
        );

        $response->assertStatus(422)->assertJsonValidationErrors(['date_to']);
    }
}
