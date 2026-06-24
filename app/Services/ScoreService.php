<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Score FlowFin (0–100) — gamificação mensal (Pilar 4 — Mentalidade).
 *
 * Média ponderada de até 3 fatores, cada um normalizado para 0–100:
 *   1. Consistência de registro — peso 40%: proporção de dias do mês com ≥1 registro.
 *   2. Orçamentos respeitados   — peso 30%: proporção de categorias com orçamento que
 *      terminaram o mês dentro do limite (consumido ≤ limite).
 *   3. Progresso na meta de economia — peso 30%: % da meta mensal atingido (capado a 100%).
 *
 * Renormalização (decisão do Worker, alinhada à Spec): se um fator não se aplica
 * (nenhum orçamento definido, ou nenhuma meta definida), ele é EXCLUÍDO da média e os
 * pesos restantes são reescalados para somar 100%. A consistência está sempre presente,
 * logo o Score nunca fica indefinido. Cada fator expõe `included` para transparência.
 *
 * Não tem cache próprio: compõe agregados já cacheados (DashboardService, BudgetService,
 * SavingsGoalService), portanto é invalidado de forma coerente junto com eles.
 */
class ScoreService
{
    private const PESO_CONSISTENCIA = 40;

    private const PESO_ORCAMENTOS = 30;

    private const PESO_META = 30;

    public function __construct(
        private DashboardService $dashboard,
        private BudgetService $budgets,
        private SavingsGoalService $savingsGoal,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user, ?string $month = null): array
    {
        $month = $this->dashboard->normalizeMonth($month);

        $consistencia = $this->consistencyFactor($user->id, $month);
        $orcamentos = $this->budgetsFactor($user, $month);
        $meta = $this->goalFactor($user, $month);

        $factors = [
            'consistency' => [
                'value' => $consistencia,
                'weight' => self::PESO_CONSISTENCIA,
                'included' => true,
            ],
            'budgets' => [
                'value' => $orcamentos,
                'weight' => self::PESO_ORCAMENTOS,
                'included' => $orcamentos !== null,
            ],
            'savings_goal' => [
                'value' => $meta,
                'weight' => self::PESO_META,
                'included' => $meta !== null,
            ],
        ];

        return [
            'month' => $month,
            'score' => $this->weightedScore($factors),
            'factors' => $factors,
        ];
    }

    /**
     * Consistência: proporção de dias do mês com ≥1 registro (0–100).
     *
     * Denominador = número de dias do mês de referência (decisão do Worker: usar o mês
     * de calendário completo mantém a métrica estável e comparável entre meses).
     */
    private function consistencyFactor(int $userId, string $month): float
    {
        $start = CarbonImmutable::createFromFormat('Y-m', $month)->startOfMonth();
        $end = $start->endOfMonth();
        $daysInMonth = (int) $start->format('t');

        $diasComRegistro = Transaction::query()
            ->where('user_id', $userId)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->distinct()
            ->count('date');

        return round(min($daysInMonth, $diasComRegistro) * 100 / $daysInMonth, 1);
    }

    /**
     * Orçamentos respeitados: proporção de orçamentos com consumo ≤ limite (0–100).
     * Null quando o usuário não definiu nenhum orçamento (fator excluído da média).
     */
    private function budgetsFactor(User $user, string $month): ?float
    {
        $status = $this->budgets->statusForUser($user, $month);

        if ($status === []) {
            return null;
        }

        $respeitados = 0;
        foreach ($status as $budget) {
            if ((int) $budget['consumed'] <= (int) $budget['monthly_limit']) {
                $respeitados++;
            }
        }

        return round($respeitados * 100 / count($status), 1);
    }

    /**
     * Progresso na meta de economia (0–100, capado). Null quando não há meta definida.
     */
    private function goalFactor(User $user, string $month): ?float
    {
        $goal = $this->savingsGoal->forUser($user, $month);

        if ($goal['progress_pct'] === null) {
            return null;
        }

        return min(100.0, (float) $goal['progress_pct']);
    }

    /**
     * Média ponderada dos fatores incluídos, com pesos reescalados para somar 100%.
     *
     * @param  array<string, array{value: float|null, weight: int, included: bool}>  $factors
     */
    private function weightedScore(array $factors): int
    {
        $somaPesos = 0;
        $somaPonderada = 0.0;

        foreach ($factors as $factor) {
            if ($factor['included'] && $factor['value'] !== null) {
                $somaPesos += $factor['weight'];
                $somaPonderada += $factor['value'] * $factor['weight'];
            }
        }

        if ($somaPesos === 0) {
            return 0;
        }

        return (int) round($somaPonderada / $somaPesos);
    }
}
