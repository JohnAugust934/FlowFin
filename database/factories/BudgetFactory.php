<?php

namespace Database\Factories;

use App\Models\Budget;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Budget>
 */
class BudgetFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            // Limite mensal em centavos (inteiro), entre R$ 50,00 e R$ 3.000,00.
            'monthly_limit' => fake()->numberBetween(5000, 300000),
        ];
    }
}
