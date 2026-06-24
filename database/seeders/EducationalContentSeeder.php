<?php

namespace Database\Seeders;

use App\Models\EducationalContent;
use Illuminate\Database\Seeder;

/**
 * Mini-conteúdos educativos do sistema (não escopados por usuário).
 * Textos curtos (1 parágrafo) em PT-BR, em linguagem humana. Idempotente.
 */
class EducationalContentSeeder extends Seeder
{
    public function run(): void
    {
        $contents = [
            [
                'theme' => 'reserva_emergencia',
                'title' => 'Por que ter uma reserva de emergência',
                'body' => 'A reserva de emergência é um dinheiro guardado só para imprevistos: uma despesa médica, um conserto urgente ou uma queda na renda. O ideal é juntar, aos poucos, o equivalente a 3 a 6 meses dos seus gastos. Com ela, você enfrenta surpresas sem recorrer a empréstimos ou ao cartão de crédito.',
            ],
            [
                'theme' => '50_30_20',
                'title' => 'A regra 50-30-20',
                'body' => 'Uma forma simples de organizar o salário: separe 50% para necessidades (moradia, comida, contas), 30% para desejos (lazer, assinaturas) e 20% para guardar ou quitar dívidas. Não precisa ser exato — o importante é ter um rumo para onde o seu dinheiro vai.',
            ],
            [
                'theme' => 'habito',
                'title' => 'O poder de registrar todo dia',
                'body' => 'Anotar o que entra e o que sai, mesmo os valores pequenos, é o hábito que mais transforma a vida financeira. Em poucas semanas você começa a enxergar para onde o dinheiro vai e a tomar decisões com mais consciência. Constância vale mais que perfeição.',
            ],
            [
                'theme' => 'consciencia',
                'title' => 'Gastos invisíveis',
                'body' => 'Pequenos gastos que se repetem — um café diário, várias assinaturas, taxas automáticas — parecem inofensivos, mas somados ao longo do mês pesam no bolso. Revisar esses gastos de vez em quando costuma liberar um bom valor sem grandes sacrifícios.',
            ],
            [
                'theme' => 'meta',
                'title' => 'Metas com propósito',
                'body' => 'Guardar dinheiro fica mais fácil quando há um motivo claro: uma viagem, a entrada de um imóvel, a tranquilidade de uma reserva. Dê um nome e um prazo para cada objetivo. Ver o progresso acontecer é o que mantém a motivação em dia.',
            ],
        ];

        foreach ($contents as $content) {
            EducationalContent::updateOrCreate(
                ['title' => $content['title']],
                ['theme' => $content['theme'], 'body' => $content['body']],
            );
        }
    }
}
