<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Idempotência server-side da criação de transações (Follow-up 5.3+).
 *
 * Garante o requisito inegociável de zero duplicação: a fila offline (Task 5.2) pode
 * reenviar o mesmo POST se a resposta se perde; o mesmo `client_uuid` nunca cria duas
 * transações.
 */
class TransactionIdempotencyApiTest extends TestCase
{
    use RefreshDatabase;

    private function category(?User $user = null): Category
    {
        return Category::factory()->create([
            'user_id' => $user?->id,
            'is_predefined' => $user === null,
        ]);
    }

    private function payload(Category $category, string $clientUuid): array
    {
        return [
            'client_uuid' => $clientUuid,
            'type' => 'saida',
            'amount' => 123456,
            'category_id' => $category->id,
            'date' => '2026-06-20',
            'description' => 'Mercado',
            'classification' => 'necessidade',
        ];
    }

    public function test_resending_the_same_client_uuid_does_not_duplicate(): void
    {
        $user = User::factory()->create();
        $category = $this->category();
        $uuid = (string) Str::uuid();

        $first = $this->actingAs($user)->postJson('/api/transactions', $this->payload($category, $uuid));
        $first->assertCreated();
        $id = $first->json('data.id');

        // Reenvio (a resposta do 1º POST "se perdeu" do ponto de vista do cliente).
        $second = $this->actingAs($user)->postJson('/api/transactions', $this->payload($category, $uuid));

        $second->assertOk()                                  // 200, não 201
            ->assertJsonPath('data.id', $id)                 // mesma transação
            ->assertJsonPath('data.client_uuid', $uuid)
            ->assertJsonPath('data.amount', 123456);

        // Uma única linha persistida para aquele client_uuid.
        $this->assertSame(1, Transaction::where('user_id', $user->id)
            ->where('client_uuid', $uuid)->count());
        $this->assertSame(1, Transaction::where('user_id', $user->id)->count());
    }

    public function test_distinct_client_uuids_create_distinct_transactions(): void
    {
        $user = User::factory()->create();
        $category = $this->category();

        $this->actingAs($user)->postJson('/api/transactions', $this->payload($category, (string) Str::uuid()))->assertCreated();
        $this->actingAs($user)->postJson('/api/transactions', $this->payload($category, (string) Str::uuid()))->assertCreated();

        $this->assertSame(2, Transaction::where('user_id', $user->id)->count());
    }

    public function test_creation_without_client_uuid_keeps_current_behavior(): void
    {
        $user = User::factory()->create();
        $category = $this->category();

        $payload = $this->payload($category, (string) Str::uuid());
        unset($payload['client_uuid']);

        $this->actingAs($user)->postJson('/api/transactions', $payload)->assertCreated();
        $this->actingAs($user)->postJson('/api/transactions', $payload)->assertCreated();

        // Sem chave de idempotência, dois POSTs criam duas transações (comportamento atual).
        $this->assertSame(2, Transaction::where('user_id', $user->id)->count());
    }

    public function test_same_client_uuid_is_isolated_per_user(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $categoryA = $this->category();
        $categoryB = $this->category();
        $uuid = (string) Str::uuid();

        // O mesmo client_uuid em usuários diferentes não colide: cada um cria a sua.
        $this->actingAs($userA)->postJson('/api/transactions', $this->payload($categoryA, $uuid))->assertCreated();
        $this->actingAs($userB)->postJson('/api/transactions', $this->payload($categoryB, $uuid))->assertCreated();

        $this->assertSame(1, Transaction::where('user_id', $userA->id)->where('client_uuid', $uuid)->count());
        $this->assertSame(1, Transaction::where('user_id', $userB->id)->where('client_uuid', $uuid)->count());
    }

    public function test_it_rejects_a_malformed_client_uuid(): void
    {
        $user = User::factory()->create();
        $category = $this->category();

        $payload = $this->payload($category, 'not-a-uuid');

        $this->actingAs($user)->postJson('/api/transactions', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('client_uuid');
    }
}
