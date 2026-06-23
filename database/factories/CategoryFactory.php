<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->unique()->word(),
            'icon' => 'tag',
            'color' => fake()->hexColor(),
            'is_predefined' => false,
        ];
    }

    /**
     * Categoria pré-definida do sistema (sem dono).
     */
    public function predefined(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'is_predefined' => true,
        ]);
    }
}
