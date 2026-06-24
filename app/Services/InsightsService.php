<?php

namespace App\Services;

use App\Models\Recurrence;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * Agregados de "Consciência" (Pilar 2) servidos com cache em arquivo.
 *
 * Todos os valores monetários permanecem em CENTAVOS (inteiro) — a formatação
 * para R$ é responsabilidade da UI.
 *
 * Cobre:
 *  - Top 3 maiores gastos (as 3 transações de SAÍDA de maior valor no mês).
 *  - Linha do tempo diária (total de saídas por dia do mês, todos os dias presentes).
 *  - Comparativo mês a mês (entrou/saiu/sobrou do mês atual vs. anterior + variação %).
 *  - Detector de gastos "invisíveis" (impacto mensal combinado das recorrências de saída).
 *
 * Cache:
 *  - Agregados do mês: chave `insights:{userId}:{month}` (depende de transações).
 *  - Gastos invisíveis: chave `invisible:{userId}` (depende de recorrências, não do mês).
 */
class InsightsService
{
    public function cacheKey(int $userId, string $month): string
    {
        return "insights:{$userId}:{$month}";
    }

    public function invisibleCacheKey(int $userId): string
    {
        return "invisible:{$userId}";
    }

    /**
     * Retorna os agregados de consciência do mês, servindo do cache quando disponível.
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
     * Invalida os agregados de consciência em cache de um usuário para um mês ("aaaa-mm").
     */
    public function forget(int $userId, string $month): void
    {
        Cache::forget($this->cacheKey($userId, $month));
    }

    /**
     * Invalida o cache de gastos invisíveis de um usuário.
     */
    public function forgetInvisible(int $userId): void
    {
        Cache::forget($this->invisibleCacheKey($userId));
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
        return [
            'month' => $month,
            'top_expenses' => $this->topExpenses($userId, $month),
            'daily_timeline' => $this->dailyTimeline($userId, $month),
            'month_comparison' => $this->monthComparison($userId, $month),
        ];
    }

    /**
     * As 3 transações de saída de maior valor do mês, com categoria.
     *
     * @return array<int, array<string, mixed>>
     */
    private function topExpenses(int $userId, string $month): array
    {
        [$start, $end] = $this->monthRange($month);

        $transactions = Transaction::query()
            ->where('user_id', $userId)
            ->where('type', 'saida')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->with('category')
            ->orderByDesc('amount')
            ->orderByDesc('id')
            ->limit(3)
            ->get();

        return $transactions->map(fn (Transaction $t) => [
            'id' => $t->id,
            'description' => $t->description,
            'amount' => (int) $t->amount,
            'date' => $t->date?->format('Y-m-d'),
            'category' => $t->category ? [
                'id' => $t->category->id,
                'name' => $t->category->name,
                'icon' => $t->category->icon,
                'color' => $t->category->color,
            ] : null,
        ])->all();
    }

    /**
     * Total de saídas por dia do mês. Todos os dias do mês aparecem (0 quando não há saída).
     *
     * @return array<int, array<string, mixed>>
     */
    private function dailyTimeline(int $userId, string $month): array
    {
        [$start, $end] = $this->monthRange($month);

        $totals = Transaction::query()
            ->where('user_id', $userId)
            ->where('type', 'saida')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('date, SUM(amount) as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        // Normaliza chaves para "aaaa-mm-dd" (o driver pode devolver com hora).
        $byDay = [];
        foreach ($totals as $date => $total) {
            $byDay[CarbonImmutable::parse((string) $date)->format('Y-m-d')] = (int) $total;
        }

        $timeline = [];
        for ($day = $start; $day->lessThanOrEqualTo($end); $day = $day->addDay()) {
            $key = $day->format('Y-m-d');
            $timeline[] = ['date' => $key, 'total' => $byDay[$key] ?? 0];
        }

        return $timeline;
    }

    /**
     * Comparativo do mês atual vs. mês anterior, com variação percentual.
     *
     * @return array<string, mixed>
     */
    private function monthComparison(int $userId, string $month): array
    {
        $current = $this->monthTotals($userId, $month);
        $previousMonth = CarbonImmutable::createFromFormat('Y-m', $month)->subMonth()->format('Y-m');
        $previous = $this->monthTotals($userId, $previousMonth);

        return [
            'current' => $current,
            'previous' => ['month' => $previousMonth] + $previous,
            'variation' => [
                'entrou_pct' => $this->variationPct($current['entrou'], $previous['entrou']),
                'saiu_pct' => $this->variationPct($current['saiu'], $previous['saiu']),
                'sobrou_pct' => $this->variationPct($current['sobrou'], $previous['sobrou']),
            ],
        ];
    }

    /**
     * @return array{entrou: int, saiu: int, sobrou: int}
     */
    private function monthTotals(int $userId, string $month): array
    {
        [$start, $end] = $this->monthRange($month);
        $range = [$start->toDateString(), $end->toDateString()];

        $base = fn () => Transaction::query()
            ->where('user_id', $userId)
            ->whereBetween('date', $range);

        $entrou = (int) $base()->where('type', 'entrada')->sum('amount');
        $saiu = (int) $base()->where('type', 'saida')->sum('amount');

        return ['entrou' => $entrou, 'saiu' => $saiu, 'sobrou' => $entrou - $saiu];
    }

    /**
     * Variação percentual de `previous` → `current`. Null quando não há base de comparação.
     */
    private function variationPct(int $current, int $previous): ?float
    {
        if ($previous === 0) {
            return null;
        }

        return round(($current - $previous) * 100 / abs($previous), 1);
    }

    /**
     * Detector de gastos "invisíveis": impacto mensal combinado das recorrências de saída ativas.
     *
     * Cada recorrência é normalizada para o impacto equivalente por mês conforme a frequência.
     *
     * @return array<string, mixed>
     */
    public function invisibleSpending(User $user): array
    {
        return Cache::rememberForever(
            $this->invisibleCacheKey($user->id),
            fn () => $this->computeInvisible($user->id),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function computeInvisible(int $userId): array
    {
        $recurrences = Recurrence::query()
            ->where('user_id', $userId)
            ->where('type', 'saida')
            ->where('is_active', true)
            ->with('category')
            ->orderByDesc('amount')
            ->get();

        $items = $recurrences->map(function (Recurrence $r) {
            $impact = $this->monthlyImpact((int) $r->amount, $r->frequency);

            return [
                'id' => $r->id,
                'description' => $r->description,
                'amount' => (int) $r->amount,
                'frequency' => $r->frequency,
                'monthly_impact' => $impact,
                'category' => $r->category ? [
                    'id' => $r->category->id,
                    'name' => $r->category->name,
                    'icon' => $r->category->icon,
                    'color' => $r->category->color,
                ] : null,
            ];
        })->all();

        $total = array_sum(array_column($items, 'monthly_impact'));

        return [
            'count' => count($items),
            'total_monthly_impact' => $total,
            'items' => $items,
        ];
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

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function monthRange(string $month): array
    {
        $start = CarbonImmutable::createFromFormat('Y-m', $month)->startOfMonth();

        return [$start, $start->endOfMonth()];
    }
}
