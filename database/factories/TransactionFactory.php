<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['entrada', 'saida']);

        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'type' => $type,
            // Valor em centavos (inteiro), entre R$ 1,00 e R$ 5.000,00.
            'amount' => fake()->numberBetween(100, 500000),
            'date' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'description' => fake()->optional()->sentence(3),
            'classification' => $type === 'saida'
                ? fake()->randomElement(['necessidade', 'desejo'])
                : null,
            'is_recurring' => false,
        ];
    }

    /**
     * Entrada (receita).
     */
    public function entrada(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'entrada',
            'classification' => null,
        ]);
    }

    /**
     * Saída (despesa).
     */
    public function saida(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'saida',
            'classification' => fake()->randomElement(['necessidade', 'desejo']),
        ]);
    }
}
