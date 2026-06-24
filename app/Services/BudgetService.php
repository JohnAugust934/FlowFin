<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * Orçamentos por categoria (Pilar 3) com status semafórico, cacheado por usuário/mês.
 *
 * Limiares (sobre o percentual consumido = consumido / limite):
 *  - < 80%   → "ok"        (verde)
 *  - 80–99%  → "alerta"    (amarelo)
 *  - ≥ 100%  → "estourado" (vermelho)
 *
 * Valores em CENTAVOS (inteiro). O "consumido" é a soma das SAÍDAS do mês na categoria.
 */
class BudgetService
{
    public function cacheKey(int $userId, string $month): string
    {
        return "budgets:{$userId}:{$month}";
    }

    /**
     * Status de todos os orçamentos do usuário no mês, servido do cache quando disponível.
     *
     * @return array<int, array<string, mixed>>
     */
    public function statusForUser(User $user, ?string $month = null): array
    {
        $month = $this->normalizeMonth($month);

        return Cache::rememberForever(
            $this->cacheKey($user->id, $month),
            fn () => $this->compute($user->id, $month),
        );
    }

    /**
     * Invalida o status de orçamentos em cache de um usuário para um mês ("aaaa-mm").
     */
    public function forget(int $userId, string $month): void
    {
        Cache::forget($this->cacheKey($userId, $month));
    }

    public function normalizeMonth(?string $month): string
    {
        if ($month === null || $month === '') {
            return CarbonImmutable::now()->format('Y-m');
        }

        return CarbonImmutable::createFromFormat('Y-m', $month)->format('Y-m');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function compute(int $userId, string $month): array
    {
        $start = CarbonImmutable::createFromFormat('Y-m', $month)->startOfMonth();
        $end = $start->endOfMonth();

        $budgets = Budget::query()
            ->where('user_id', $userId)
            ->with('category')
            ->get();

        // Consumo por categoria (saídas do mês) em uma única consulta agregada.
        $consumedByCategory = Transaction::query()
            ->where('user_id', $userId)
            ->where('type', 'saida')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        return $budgets->map(function (Budget $budget) use ($consumedByCategory) {
            $limit = (int) $budget->monthly_limit;
            $consumed = (int) ($consumedByCategory[$budget->category_id] ?? 0);
            $pct = $limit > 0 ? round($consumed * 100 / $limit, 1) : 0.0;

            return [
                'id' => $budget->id,
                'category' => $budget->category ? [
                    'id' => $budget->category->id,
                    'name' => $budget->category->name,
                    'icon' => $budget->category->icon,
                    'color' => $budget->category->color,
                ] : null,
                'monthly_limit' => $limit,
                'consumed' => $consumed,
                'remaining' => $limit - $consumed,
                'percentage' => $pct,
                'status' => $this->status($pct),
            ];
        })->all();
    }

    /**
     * Estado semafórico a partir do percentual consumido.
     */
    private function status(float $pct): string
    {
        return match (true) {
            $pct >= 100 => 'estourado',
            $pct >= 80 => 'alerta',
            default => 'ok',
        };
    }
}
