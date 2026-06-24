<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Recurrence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurrenceApiTest extends TestCase
{
    use RefreshDatabase;

    private function category(?User $user = null): Category
    {
        return Category::factory()->create([
            'user_id' => $user?->id,
            'is_predefined' => $user === null,
        ]);
    }

    public function test_it_lists_only_the_authenticated_users_recurrences_paginated(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Recurrence::factory()->count(25)->create(['user_id' => $user->id, 'category_id' => $this->category()->id]);
        Recurrence::factory()->count(3)->create(['user_id' => $other->id, 'category_id' => $this->category()->id]);

        $response = $this->actingAs($user)->getJson('/api/recurrences');

        $response->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('meta.total', 25);
    }

    public function test_it_creates_a_recurrence_in_cents(): void
    {
        $user = User::factory()->create();
        $category = $this->category();

        $response = $this->actingAs($user)->postJson('/api/recurrences', [
            'description' => 'Aluguel',
            'type' => 'saida',
            'amount' => 150000,
            'frequency' => 'mensal',
            'start_date' => '2026-06-05',
            'category_id' => $category->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.amount', 150000)
            ->assertJsonPath('data.description', 'Aluguel');

        $this->assertDatabaseHas('recurrences', ['user_id' => $user->id, 'amount' => 150000, 'description' => 'Aluguel']);
    }

    public function test_it_validates_required_fields_with_422(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/recurrences', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['description', 'type', 'amount', 'frequency', 'start_date']);
    }

    public function test_it_rejects_a_category_from_another_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $foreign = $this->category($other);

        $response = $this->actingAs($user)->postJson('/api/recurrences', [
            'description' => 'X', 'type' => 'saida', 'amount' => 1000,
            'frequency' => 'mensal', 'start_date' => '2026-06-01', 'category_id' => $foreign->id,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['category_id']);
    }

    public function test_it_soft_deletes_a_recurrence(): void
    {
        $user = User::factory()->create();
        $recurrence = Recurrence::factory()->create(['user_id' => $user->id, 'category_id' => $this->category()->id]);

        $this->actingAs($user)->deleteJson("/api/recurrences/{$recurrence->id}")->assertOk();
        $this->assertSoftDeleted('recurrences', ['id' => $recurrence->id]);
    }

    public function test_a_user_cannot_access_another_users_recurrence(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $foreign = Recurrence::factory()->create(['user_id' => $other->id, 'category_id' => $this->category()->id]);

        $this->actingAs($user)->getJson("/api/recurrences/{$foreign->id}")->assertNotFound();
    }

    public function test_projection_lists_monthly_occurrences_and_totals(): void
    {
        $user = User::factory()->create();
        $category = $this->category();

        // Conta fixa mensal de saída de R$ 100,00 a partir de janeiro.
        Recurrence::factory()->saida()->create([
            'user_id' => $user->id, 'category_id' => $category->id,
            'description' => 'Internet', 'amount' => 10000, 'frequency' => 'mensal',
            'start_date' => '2026-01-10',
        ]);
        // Recorrência semanal de saída de R$ 50,00.
        Recurrence::factory()->saida()->create([
            'user_id' => $user->id, 'category_id' => $category->id,
            'description' => 'Feira', 'amount' => 5000, 'frequency' => 'semanal',
            'start_date' => '2026-06-01',
        ]);
        // Entrada mensal (salário) R$ 300,00.
        Recurrence::factory()->entrada()->create([
            'user_id' => $user->id, 'category_id' => $category->id,
            'description' => 'Salário', 'amount' => 30000, 'frequency' => 'mensal',
            'start_date' => '2026-01-05',
        ]);
        // Inativa: não entra na projeção.
        Recurrence::factory()->inactive()->saida()->create([
            'user_id' => $user->id, 'category_id' => $category->id,
            'amount' => 99999, 'frequency' => 'mensal', 'start_date' => '2026-01-01',
        ]);

        $response = $this->actingAs($user)->getJson('/api/recurrences/projection?month=2026-06');

        $response->assertOk()
            ->assertJsonPath('month', '2026-06')
            ->assertJsonPath('totals.entrada', 30000);

        // Junho/2026: mensal incide 1x (10000); semanal a partir de 01/06 incide nos dias 1,8,15,22,29 = 5x (25000).
        $this->assertSame(35000, $response->json('totals.saida'));
        $this->assertSame(30000 - 35000, $response->json('totals.saldo'));
    }
}
