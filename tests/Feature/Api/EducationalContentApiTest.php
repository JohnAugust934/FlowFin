<?php

namespace Tests\Feature\Api;

use App\Models\EducationalContent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EducationalContentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_contents_paginated_20_per_page(): void
    {
        $user = User::factory()->create();
        EducationalContent::factory()->count(25)->create();

        $response = $this->actingAs($user)->getJson('/api/educational-contents');

        $response->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.total', 25)
            ->assertJsonStructure(['data' => [['id', 'title', 'theme', 'body']]]);
    }

    public function test_it_filters_by_theme(): void
    {
        $user = User::factory()->create();
        EducationalContent::factory()->count(3)->create(['theme' => 'reserva_emergencia']);
        EducationalContent::factory()->count(2)->create(['theme' => '50_30_20']);

        $this->actingAs($user)->getJson('/api/educational-contents?theme=reserva_emergencia')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/educational-contents')->assertUnauthorized();
    }
}
