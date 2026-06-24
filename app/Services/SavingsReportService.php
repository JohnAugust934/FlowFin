<?php

namespace App\Services;

use App\Models\Recurrence;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * Relatório "Onde economizar" (Pilar 3 — Economia).
 *
 * Gera sugestões DETERMINÍSTICAS (sem ML) de onde o usuário pode cortar gastos,
 * a partir de duas fontes:
 *  - As maiores categorias de SAÍDA classificadas como "Desejo" no mês.
 *  - Os gastos recorrentes de saída ativos (assinaturas / contas fixas).
 *
 * Para cada fonte sugere-se um corte percentual fixo e calcula-se a economia
 * mensal correspondente (em CENTAVOS). A lista é priorizada pela maior economia
 * potencial. Valores sempre em CENTAVOS inteiros; a formatação para R$ é da UI
 * (o campo `message` já vem em linguagem humana, pronto para exibição).
 *
 * Percentuais de corte adotados (decisão do Worker — a Spec só dá 30% como exemplo):
 *  - Categorias de "Desejo": 30% (alinhado ao exemplo da Spec: "Reduza 30% em delivery").
 *  - Gastos recorrentes: 20% (compromissos fixos são mais difíceis de cortar; corte conservador
 *    representando renegociação/troca de plano em vez de cancelamento total).
 *
 * Cache: chave `savings_report:{userId}:{month}` (cálculo leve, mas mantém o padrão
 * dos demais agregados). Invalidado por TransactionObserver (parte "desejo") e
 * RecurrenceObserver (parte "recorrentes").
 */
class SavingsReportService
{
    /** Corte sugerido para categorias de "Desejo". */
    private const CORTE_DESEJO_PCT = 30;

    /** Corte sugerido para gastos recorrentes (assinaturas/contas fixas). */
    private const CORTE_RECORRENTE_PCT = 20;

    public function cacheKey(int $userId, string $month): string
    {
        return "savings_report:{$userId}:{$month}";
    }

    /**
     * Sugestões de economia do mês, servidas do cache quando disponível.
     *
     * @return array<string, mixed>
     */
    public function forUser(User $user, ?string $month = null): array
    {
        $month = $this->normalizeMonth($month);

        return Cache::rememberForever(
            $this->cacheKey($user->id, $month),
            fn () => $this->compute($user->id, $month),
        );
    }

    /**
     * Invalida o relatório em cache de um usuário para um mês ("aaaa-mm").
     */
    public function forget(int $userId, string $month): void
    {
        Cache::forget($this->cacheKey($userId, $month));
    }

    /**
     * Normaliza o mês para "aaaa-mm" (default = mês atual).
     */
    public function normalizeMonth(?string $month): string
    {
        if ($month === null || $month === '') {
            return CarbonImmutable::now()->format('Y-m');
        }

        return CarbonImmutable::createFromFormat('Y-m', $month)->format('Y-m');
    }

    /**
     * @return array<string, mixed>
     */
    private function compute(int $userId, string $month): array
    {
        $suggestions = array_merge(
            $this->fromDesejoCategories($userId, $month),
            $this->fromRecurrences($userId),
        );

        // Prioriza pela maior economia potencial.
        usort($suggestions, fn (array $a, array $b) => $b['estimated_savings'] <=> $a['estimated_savings']);

        $total = array_sum(array_column($suggestions, 'estimated_savings'));

        return [
            'month' => $month,
            'total_potential_savings' => $total,
            'count' => count($suggestions),
            'suggestions' => $suggestions,
        ];
    }

    /**
     * Maiores categorias de saída classificadas como "Desejo" no mês.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fromDesejoCategories(int $userId, string $month): array
    {
        $start = CarbonImmutable::createFromFormat('Y-m', $month)->startOfMonth();
        $range = [$start->toDateString(), $start->endOfMonth()->toDateString()];

        $rows = Transaction::query()
            ->where('user_id', $userId)
            ->where('type', 'saida')
            ->where('classification', 'desejo')
            ->whereBetween('date', $range)
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->with('category')
            ->get();

        return $rows->map(function ($row) {
            $current = (int) $row->total;
            $savings = (int) round($current * self::CORTE_DESEJO_PCT / 100);
            $name = $row->category?->name ?? 'Sem categoria';

            return [
                'type' => 'categoria_desejo',
                'reference_id' => $row->category_id,
                'label' => $name,
                'current_amount' => $current,
                'cut_pct' => self::CORTE_DESEJO_PCT,
                'estimated_savings' => $savings,
                'category' => $row->category ? [
                    'id' => $row->category->id,
                    'name' => $row->category->name,
                    'icon' => $row->category->icon,
                    'color' => $row->category->color,
                ] : null,
                'message' => sprintf(
                    'Reduza %d%% em %s e economize %s por mês.',
                    self::CORTE_DESEJO_PCT,
                    $name,
                    Money::formatBRL($savings),
                ),
            ];
        })->all();
    }

    /**
     * Gastos recorrentes de saída ativos (assinaturas/contas fixas).
     *
     * @return array<int, array<string, mixed>>
     */
    private function fromRecurrences(int $userId): array
    {
        $recurrences = Recurrence::query()
            ->where('user_id', $userId)
            ->where('type', 'saida')
            ->where('is_active', true)
            ->with('category')
            ->get();

        return $recurrences->map(function (Recurrence $r) {
            $current = $this->monthlyImpact((int) $r->amount, $r->frequency);
            $savings = (int) round($current * self::CORTE_RECORRENTE_PCT / 100);

            return [
                'type' => 'recorrente',
                'reference_id' => $r->id,
                'label' => $r->description,
                'current_amount' => $current,
                'cut_pct' => self::CORTE_RECORRENTE_PCT,
                'estimated_savings' => $savings,
                'category' => $r->category ? [
                    'id' => $r->category->id,
                    'name' => $r->category->name,
                    'icon' => $r->category->icon,
                    'color' => $r->category->color,
                ] : null,
                'message' => sprintf(
                    'Revise %s (gasto recorrente): cortar %d%% economiza %s por mês.',
                    $r->description,
                    self::CORTE_RECORRENTE_PCT,
                    Money::formatBRL($savings),
                ),
            ];
        })->all();
    }

    /**
     * Impacto mensal (centavos) de um valor recorrente conforme a frequência.
     */
    private function monthlyImpact(int $amount, string $frequency): int
    {
        return match ($frequency) {
            'diaria' => (int) round($amount * 365 / 12),
            'semanal' => (int) round($amount * 52 / 12),
            'anual' => (int) round($amount / 12),
            default => $amount, // mensal
        };
    }
}
