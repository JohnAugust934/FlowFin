<?php

namespace Database\Factories;

use App\Models\Goal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Goal>
 */
class GoalFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement(['Reserva de emergência', 'Viagem', 'Notebook novo', 'Entrada do carro']),
            'description' => fake()->optional()->sentence(),
            // Valores em centavos (inteiro).
            'target_amount' => fake()->numberBetween(100000, 5000000),
            'saved_amount' => fake()->numberBetween(0, 100000),
            'due_date' => fake()->optional()->dateTimeBetween('now', '+2 years')?->format('Y-m-d'),
            'priority' => fake()->randomElement(['baixa', 'media', 'alta']),
        ];
    }
}
