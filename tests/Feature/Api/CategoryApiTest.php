<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_predefined_and_own_categories_only(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $predefined = Category::factory()->predefined()->create(['name' => 'Moradia']);
        $own = Category::factory()->create(['user_id' => $user->id, 'name' => 'Pets']);
        $foreign = Category::factory()->create(['user_id' => $other->id, 'name' => 'Secreta']);

        $response = $this->actingAs($user)->getJson('/api/categories');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($predefined->id));
        $this->assertTrue($ids->contains($own->id));
        $this->assertFalse($ids->contains($foreign->id));   // não vê de outro usuário
    }

    public function test_it_creates_a_custom_category_for_the_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/categories', [
            'name' => 'Pets',
            'icon' => 'paw',
            'color' => '#16A34A',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Pets')
            ->assertJsonPath('data.is_predefined', false);

        $this->assertDatabaseHas('categories', [
            'name' => 'Pets',
            'user_id' => $user->id,
            'is_predefined' => false,
        ]);
    }

    public function test_it_validates_category_creation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/categories', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_it_updates_own_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'name' => 'Antigo']);

        $this->actingAs($user)->putJson("/api/categories/{$category->id}", ['name' => 'Novo'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Novo');

        $this->assertSame('Novo', $category->refresh()->name);
    }

    public function test_predefined_categories_cannot_be_updated_or_deleted(): void
    {
        $user = User::factory()->create();
        $predefined = Category::factory()->predefined()->create(['name' => 'Moradia']);

        $this->actingAs($user)->putJson("/api/categories/{$predefined->id}", ['name' => 'Hackeado'])
            ->assertNotFound();
        $this->actingAs($user)->deleteJson("/api/categories/{$predefined->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('categories', ['id' => $predefined->id, 'name' => 'Moradia', 'deleted_at' => null]);
    }

    public function test_it_soft_deletes_own_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->deleteJson("/api/categories/{$category->id}")->assertOk();

        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_a_user_cannot_modify_another_users_category(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $foreign = Category::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user)->putJson("/api/categories/{$foreign->id}", ['name' => 'X'])->assertNotFound();
        $this->actingAs($user)->deleteJson("/api/categories/{$foreign->id}")->assertNotFound();
    }
}
