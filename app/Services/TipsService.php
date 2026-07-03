<?php

namespace App\Services;

use App\Models\User;

/**
 * Motor de dicas contextuais DETERMINÍSTICAS (sem ML).
 *
 * Observa o comportamento do mês e devolve uma lista de dicas em PT-BR, em linguagem
 * humana, priorizadas (alertas primeiro, depois reforços positivos, depois educação geral).
 * Cada dica é composta a partir de agregados já existentes (consciência, orçamentos,
 * meta de economia e streak), sem reimplementar nenhum cálculo.
 *
 * Cada dica: { code, level: ('alerta'|'positivo'|'educativo'), title, message, theme }.
 */
class TipsService
{
    public function __construct(
        private InsightsService $insights,
        private BudgetService $budgets,
        private SavingsGoalService $savingsGoal,
        private StreakService $streak,
    ) {}

    /**
     * @return array<int, array<string, string>>
     */
    public function forUser(User $user, ?string $month = null): array
    {
        $month = $this->insights->normalizeMonth($month);

        $alertas = [];
        $positivos = [];

        // --- Alertas (comportamento que pede atenção) ---

        // Orçamento estourado.
        $estourados = array_filter(
            $this->budgets->statusForUser($user, $month),
            fn ($b) => $b['status'] === 'estourado',
        );
        if ($estourados !== []) {
            $nomes = implode(', ', array_map(
                fn ($b) => $b['category']['name'] ?? 'sem categoria',
                $estourados,
            ));
            $alertas[] = $this->tip(
                'orcamento_estourado',
                'alerta',
                'Orçamento estourado',
                "Você passou do limite em: {$nomes}. Que tal revisar esses gastos antes do fim do mês?",
                'orcamento',
            );
        }

        // Gastou mais que no mês anterior.
        $comparativo = $this->insights->forUser($user, $month)['month_comparison'];
        $saiuPct = $comparativo['variation']['saiu_pct'] ?? null;
        if ($saiuPct !== null && $saiuPct > 0) {
            $alertas[] = $this->tip(
                'gasto_acima_mes_anterior',
                'alerta',
                'Gastos em alta',
                "Suas saídas estão {$saiuPct}% acima do mês passado. Vale olhar onde dá para segurar.",
                'consciencia',
            );
        }

        // --- Reforços positivos ---

        // Streak ativo.
        $streak = $this->streak->compute($user)['current_streak'];
        if ($streak >= 2) {
            $positivos[] = $this->tip(
                'streak_ativo',
                'positivo',
                'Sequência em dia',
                "Você está há {$streak} dias seguidos registrando suas finanças. Continue assim para fortalecer o hábito!",
                'habito',
            );
        }

        // Meta de economia atingida.
        $meta = $this->savingsGoal->forUser($user, $month);
        if ($meta['achieved']) {
            $positivos[] = $this->tip(
                'meta_atingida',
                'positivo',
                'Meta alcançada',
                'Parabéns! Você já atingiu sua meta de economia deste mês. Que tal destinar o excedente a um objetivo?',
                'meta',
            );
        }

        // --- Educação geral (sempre presente, fecha a lista) ---
        $educativas = [
            $this->tip(
                'reserva_emergencia',
                'educativo',
                'Reserva de emergência',
                'Tente guardar aos poucos uma reserva equivalente a 3 a 6 meses dos seus gastos. Ela protege você de imprevistos sem precisar se endividar.',
                'reserva_emergencia',
            ),
            $this->tip(
                'regra_50_30_20',
                'educativo',
                'Regra 50-30-20',
                'Uma divisão simples da sua renda: 50% para necessidades, 30% para desejos e 20% para guardar ou quitar dívidas.',
                '50_30_20',
            ),
        ];

        return array_values(array_merge($alertas, $positivos, $educativas));
    }

    /**
     * @return array<string, string>
     */
    private function tip(string $code, string $level, string $title, string $message, string $theme): array
    {
        return compact('code', 'level', 'title', 'message', 'theme');
    }
}
