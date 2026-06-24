<?php

namespace Database\Factories;

use App\Models\EducationalContent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EducationalContent>
 */
class EducationalContentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'theme' => fake()->randomElement(['reserva_emergencia', '50_30_20', 'habito', 'consciencia']),
            'body' => fake()->paragraph(),
        ];
    }
}
