<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Recurrence;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Recurrence>
 */
class RecurrenceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-6 months', 'now');

        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'description' => fake()->randomElement(['Aluguel', 'Streaming', 'Academia', 'Internet', 'Plano de saúde']),
            'type' => 'saida',
            // Valor da conta fixa em centavos (inteiro), entre R$ 10,00 e R$ 2.000,00.
            'amount' => fake()->numberBetween(1000, 200000),
            'frequency' => 'mensal',
            'start_date' => $start->format('Y-m-d'),
            'next_due_date' => fake()->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
            'is_active' => true,
        ];
    }

    /**
     * Conta fixa de saída (assinatura/despesa recorrente).
     */
    public function saida(): static
    {
        return $this->state(fn (array $attributes) => ['type' => 'saida']);
    }

    /**
     * Recorrência de entrada (ex.: salário).
     */
    public function entrada(): static
    {
        return $this->state(fn (array $attributes) => ['type' => 'entrada']);
    }

    /**
     * Recorrência inativa (encerrada).
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'next_due_date' => null,
        ]);
    }
}
