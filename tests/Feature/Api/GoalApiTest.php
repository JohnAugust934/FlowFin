<?php

namespace Tests\Feature\Api;

use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoalApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_goal_scoped_to_the_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/goals', [
            'name' => 'Reserva de emergência',
            'description' => 'Dormir tranquilo',
            'target_amount' => 600000,
            'priority' => 'alta',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Reserva de emergência')
            ->assertJsonPath('data.target_amount', 600000)
            ->assertJsonPath('data.priority', 'alta');

        $this->assertDatabaseHas('goals', ['user_id' => $user->id, 'name' => 'Reserva de emergência']);
    }

    public function test_it_lists_goals_ordered_by_priority(): void
    {
        $user = User::factory()->create();
        Goal::factory()->create(['user_id' => $user->id, 'name' => 'Baixa', 'priority' => 'baixa', 'due_date' => null]);
        Goal::factory()->create(['user_id' => $user->id, 'name' => 'Alta', 'priority' => 'alta', 'due_date' => null]);
        Goal::factory()->create(['user_id' => $user->id, 'name' => 'Média', 'priority' => 'media', 'due_date' => null]);

        $response = $this->actingAs($user)->getJson('/api/goals');

        $response->assertOk()->assertJsonCount(3, 'data');
        $this->assertSame(['Alta', 'Média', 'Baixa'], collect($response->json('data'))->pluck('name')->all());
    }

    public function test_progress_pct_reflects_saved_over_target_capped(): void
    {
        $user = User::factory()->create();
        Goal::factory()->create([
            'user_id' => $user->id, 'name' => 'M', 'target_amount' => 10000, 'saved_amount' => 2500, 'priority' => 'media',
        ]);

        $this->actingAs($user)->getJson('/api/goals')
            ->assertOk()
            ->assertJsonPath('data.0.progress_pct', 25)
            ->assertJsonPath('data.0.remaining_amount', 7500);
    }

    public function test_it_does_not_list_other_users_goals(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Goal::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user)->getJson('/api/goals')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_it_soft_deletes_a_goal(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->deleteJson("/api/goals/{$goal->id}")->assertOk();

        $this->assertSoftDeleted('goals', ['id' => $goal->id]);
    }

    public function test_it_cannot_delete_another_users_goal(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user)->deleteJson("/api/goals/{$goal->id}")->assertNotFound();
    }

    public function test_validation_rejects_missing_name_and_target(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/goals', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'target_amount']);
    }

    public function test_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/goals')->assertUnauthorized();
    }
}
