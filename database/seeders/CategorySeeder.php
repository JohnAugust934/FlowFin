<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Categorias pré-definidas do sistema (user_id nulo).
     * Cada uma com ícone e cor para uso consistente na interface.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Moradia',      'icon' => 'home',         'color' => '#2563EB'],
            ['name' => 'Alimentação',  'icon' => 'shopping-cart', 'color' => '#16A34A'],
            ['name' => 'Transporte',   'icon' => 'truck',        'color' => '#0891B2'],
            ['name' => 'Saúde',        'icon' => 'heart',        'color' => '#DC2626'],
            ['name' => 'Lazer',        'icon' => 'sparkles',     'color' => '#9333EA'],
            ['name' => 'Educação',     'icon' => 'academic-cap', 'color' => '#CA8A04'],
            ['name' => 'Assinaturas',  'icon' => 'rectangle-stack', 'color' => '#DB2777'],
            ['name' => 'Compras',      'icon' => 'shopping-bag', 'color' => '#EA580C'],
            ['name' => 'Outros',       'icon' => 'ellipsis-horizontal', 'color' => '#64748B'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['name' => $category['name'], 'user_id' => null, 'is_predefined' => true],
                ['icon' => $category['icon'], 'color' => $category['color']],
            );
        }
    }
}
