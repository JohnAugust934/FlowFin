<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavingsGoalApiTest extends TestCase
{
    use RefreshDatabase;

    private function category(): Category
    {
        return Category::factory()->create(['user_id' => null, 'is_predefined' => true]);
    }

    public function test_it_sets_and_returns_the_monthly_savings_goal(): void
    {
        $user = User::factory()->create(['monthly_savings_goal' => null]);

        $response = $this->actingAs($user)->putJson('/api/savings-goal', [
            'monthly_savings_goal' => 50000,
        ]);

        $response->assertOk()->assertJsonPath('goal', 50000);
        $this->assertSame(50000, $user->fresh()->monthly_savings_goal);
    }

    public function test_it_clears_the_goal_with_null(): void
    {
        $user = User::factory()->create(['monthly_savings_goal' => 50000]);

        $response = $this->actingAs($user)->putJson('/api/savings-goal', [
            'monthly_savings_goal' => null,
        ]);

        $response->assertOk()->assertJsonPath('goal', null)->assertJsonPath('progress_pct', null);
        $this->assertNull($user->fresh()->monthly_savings_goal);
    }

    public function test_progress_reflects_month_leftover(): void
    {
        $user = User::factory()->create(['monthly_savings_goal' => 50000]);
        $category = $this->category();

        // Junho: entrou 100000, saiu 70000 → sobrou 30000. Meta 50000 → 60%.
        Transaction::factory()->entrada()->create(['user_id' => $user->id, 'category_id' => $category->id, 'amount' => 100000, 'date' => '2026-06-05']);
        Transaction::factory()->saida()->create(['user_id' => $user->id, 'category_id' => $category->id, 'amount' => 70000, 'date' => '2026-06-06']);

        $response = $this->actingAs($user)->getJson('/api/savings-goal?month=2026-06');

        $response->assertOk()
            ->assertJsonPath('goal', 50000)
            ->assertJsonPath('saved', 30000)
            ->assertJsonPath('progress_pct', 60)
            ->assertJsonPath('achieved', false);
    }

    public function test_progress_caps_at_100_when_goal_exceeded(): void
    {
        $user = User::factory()->create(['monthly_savings_goal' => 20000]);
        $category = $this->category();

        Transaction::factory()->entrada()->create(['user_id' => $user->id, 'category_id' => $category->id, 'amount' => 100000, 'date' => '2026-06-05']);
        Transaction::factory()->saida()->create(['user_id' => $user->id, 'category_id' => $category->id, 'amount' => 30000, 'date' => '2026-06-06']);

        $response = $this->actingAs($user)->getJson('/api/savings-goal?month=2026-06');

        $response->assertOk()
            ->assertJsonPath('saved', 70000)
            ->assertJsonPath('progress_pct', 100)
            ->assertJsonPath('achieved', true);
    }

    public function test_validation_rejects_negative_and_requires_field_presence(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->putJson('/api/savings-goal', ['monthly_savings_goal' => -10])
            ->assertStatus(422)->assertJsonValidationErrors(['monthly_savings_goal']);

        $this->actingAs($user)->putJson('/api/savings-goal', [])
            ->assertStatus(422)->assertJsonValidationErrors(['monthly_savings_goal']);
    }

    public function test_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/savings-goal')->assertUnauthorized();
    }
}
