<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * Dados de teste para validação manual do MVP (Stage 2).
 * Idempotente: cria/atualiza o usuário de teste e recria suas transações.
 *
 * Rodar: php artisan db:seed --class=TestDataSeeder
 */
class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        // Garante as categorias pré-definidas.
        $this->call(CategorySeeder::class);

        // Usuário de teste com e-mail JÁ verificado.
        $user = User::updateOrCreate(
            ['email' => 'teste@flowfin.com.br'],
            [
                'name' => 'Usuário de Teste',
                'password' => Hash::make('senha1234'),
                'email_verified_at' => now(),
                'monthly_income' => 500000, // R$ 5.000,00
            ],
        );

        // Limpa transações anteriores deste usuário (inclui soft-deleted) para re-rodar limpo.
        Transaction::withTrashed()->where('user_id', $user->id)->forceDelete();

        $cat = fn (string $name) => Category::where('name', $name)
            ->where('is_predefined', true)->value('id');

        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonthNoOverflow()->startOfMonth();

        // [categoria, tipo, valor_centavos, classificação|null, dia, descrição]
        $rows = [
            // ----- MÊS ATUAL -----
            ['Alimentação', 'saida',   8500,  'necessidade', 2,  'Supermercado'],
            ['Alimentação', 'saida',   4200,  'desejo',      5,  'Delivery'],
            ['Alimentação', 'saida',   3100,  'desejo',      12, 'Lanche'],
            ['Transporte',  'saida',   12000, 'necessidade', 3,  'Combustível'],
            ['Transporte',  'saida',   2500,  'necessidade', 9,  'App de transporte'],
            ['Moradia',     'saida',   150000,'necessidade', 5,  'Aluguel'],
            ['Lazer',       'saida',   6000,  'desejo',      8,  'Cinema'],
            ['Lazer',       'saida',   9000,  'desejo',      15, 'Bar com amigos'],
            ['Assinaturas', 'saida',   3990,  'desejo',      1,  'Streaming'],
            ['Assinaturas', 'saida',   1990,  'desejo',      1,  'Música'],
            ['Saúde',       'saida',   8000,  'necessidade', 10, 'Farmácia'],
            ['Compras',     'saida',   13000, 'desejo',      14, 'Roupas'],
            ['Outros',      'saida',   2000,  null,          7,  'Diversos (sem classificação)'],
            ['Outros',      'entrada', 500000,null,          5,  'Salário'],
            ['Outros',      'entrada', 30000, null,          16, 'Freelance'],

            // ----- MÊS ANTERIOR (para seletor de mês + comparativo) -----
            ['Alimentação', 'saida',   9200,  'necessidade', 4,  'Supermercado'],
            ['Transporte',  'saida',   11000, 'necessidade', 6,  'Combustível'],
            ['Moradia',     'saida',   150000,'necessidade', 5,  'Aluguel'],
            ['Lazer',       'saida',   4500,  'desejo',      18, 'Show'],
            ['Assinaturas', 'saida',   3990,  'desejo',      1,  'Streaming'],
            ['Compras',     'saida',   7000,  'desejo',      22, 'Eletrônico'],
            ['Outros',      'entrada', 500000,null,          5,  'Salário'],
        ];

        $thisCount = 15; // primeiras 15 linhas são do mês atual
        foreach ($rows as $i => [$catName, $type, $amount, $classif, $day, $desc]) {
            $base = $i < $thisCount ? $thisMonth : $lastMonth;
            Transaction::create([
                'user_id' => $user->id,
                'category_id' => $cat($catName),
                'type' => $type,
                'amount' => $amount,
                'date' => $base->copy()->day($day)->toDateString(),
                'description' => $desc,
                'classification' => $type === 'saida' ? $classif : null,
                'is_recurring' => in_array($catName, ['Moradia', 'Assinaturas'], true),
            ]);
        }

        $this->command->info('Usuário de teste: teste@flowfin.com.br / senha1234 (e-mail verificado)');
        $this->command->info('Transações criadas: '.count($rows).' (mês atual + mês anterior)');
    }
}
