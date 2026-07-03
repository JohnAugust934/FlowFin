<?php

namespace Database\Factories;

use App\Models\Investment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Investment>
 */
class InvestmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'description' => fake()->randomElement(['Tesouro Selic', 'CDB do banco', 'Fundo de ações', 'Poupança']),
            'type' => fake()->randomElement(['Renda Fixa', 'Renda Variável', 'Poupança']),
            // Valor aplicado em centavos (inteiro).
            'amount' => fake()->numberBetween(10000, 5000000),
        ];
    }
}
