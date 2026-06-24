<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimulatorApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_computes_months_from_monthly_and_target(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/goals/simulate', [
            'monthly_amount' => 10000, 'target_amount' => 100000,
        ])->assertOk()
            ->assertJsonPath('months', 10)
            ->assertJsonPath('computed', 'months');
    }

    public function test_it_computes_target_from_monthly_and_months(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/goals/simulate', [
            'monthly_amount' => 10000, 'months' => 10,
        ])->assertOk()
            ->assertJsonPath('target_amount', 100000)
            ->assertJsonPath('computed', 'target_amount');
    }

    public function test_it_computes_monthly_from_target_and_months(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/goals/simulate', [
            'target_amount' => 100000, 'months' => 10,
        ])->assertOk()
            ->assertJsonPath('monthly_amount', 10000)
            ->assertJsonPath('computed', 'monthly_amount');
    }

    public function test_months_round_up_to_guarantee_target(): void
    {
        $user = User::factory()->create();

        // 10000 / 3000 = 3,33 → 4 meses.
        $this->actingAs($user)->postJson('/api/goals/simulate', [
            'monthly_amount' => 3000, 'target_amount' => 10000,
        ])->assertOk()->assertJsonPath('months', 4);
    }

    public function test_it_rejects_when_not_exactly_two_values_are_given(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/goals/simulate', [
            'monthly_amount' => 10000, 'target_amount' => 100000, 'months' => 10,
        ])->assertStatus(422);

        $this->actingAs($user)->postJson('/api/goals/simulate', [
            'monthly_amount' => 10000,
        ])->assertStatus(422);
    }

    public function test_endpoint_requires_authentication(): void
    {
        $this->postJson('/api/goals/simulate', ['monthly_amount' => 1000, 'months' => 2])
            ->assertUnauthorized();
    }
}
