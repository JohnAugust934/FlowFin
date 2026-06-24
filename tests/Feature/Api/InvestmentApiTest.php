<?php

namespace Tests\Feature\Api;

use App\Models\Investment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvestmentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_investment(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/investments', [
            'description' => 'Tesouro Selic', 'type' => 'Renda Fixa', 'amount' => 150000,
        ])->assertCreated()
            ->assertJsonPath('data.description', 'Tesouro Selic')
            ->assertJsonPath('data.amount', 150000);

        $this->assertDatabaseHas('investments', ['user_id' => $user->id, 'amount' => 150000]);
    }

    public function test_it_lists_with_aggregated_total_over_all_investments(): void
    {
        $user = User::factory()->create();
        Investment::factory()->create(['user_id' => $user->id, 'amount' => 100000]);
        Investment::factory()->create(['user_id' => $user->id, 'amount' => 250000]);
        Investment::factory()->create(['user_id' => $user->id, 'amount' => 50000]);

        $this->actingAs($user)->getJson('/api/investments')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('total_invested', 400000);
    }

    public function test_total_sums_all_pages_not_only_current(): void
    {
        $user = User::factory()->create();
        Investment::factory()->count(25)->create(['user_id' => $user->id, 'amount' => 1000]);

        $this->actingAs($user)->getJson('/api/investments')
            ->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('total_invested', 25000);
    }

    public function test_total_is_scoped_to_the_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Investment::factory()->create(['user_id' => $user->id, 'amount' => 100000]);
        Investment::factory()->create(['user_id' => $other->id, 'amount' => 999999]);

        $this->actingAs($user)->getJson('/api/investments')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('total_invested', 100000);
    }

    public function test_it_soft_deletes_an_investment(): void
    {
        $user = User::factory()->create();
        $investment = Investment::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->deleteJson("/api/investments/{$investment->id}")->assertOk();

        $this->assertSoftDeleted('investments', ['id' => $investment->id]);
    }

    public function test_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/investments')->assertUnauthorized();
    }
}
