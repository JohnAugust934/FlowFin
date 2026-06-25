<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Goal;
use App\Models\Investment;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountDeletionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_hard_deletes_the_account_and_all_personal_data(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);
        $tx = Transaction::factory()->create(['user_id' => $user->id, 'category_id' => $category->id]);
        $goal = Goal::factory()->create(['user_id' => $user->id]);
        $investment = Investment::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->deleteJson('/api/account', ['password' => 'password'])
            ->assertOk()
            ->assertJsonPath('message', 'Sua conta e seus dados foram excluídos definitivamente.');

        // Purge físico (LGPD): nada de soft delete — as linhas somem do banco.
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('transactions', ['id' => $tx->id]);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        $this->assertDatabaseMissing('goals', ['id' => $goal->id]);
        $this->assertDatabaseMissing('investments', ['id' => $investment->id]);
    }

    public function test_it_rejects_deletion_with_a_wrong_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->deleteJson('/api/account', ['password' => 'senha-errada'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_deletion_requires_authentication(): void
    {
        $this->deleteJson('/api/account', ['password' => 'password'])->assertUnauthorized();
    }
}
