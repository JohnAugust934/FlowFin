<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * Calcula e serve, com cache em arquivo, os agregados mensais do dashboard.
 *
 * Todos os valores monetários permanecem em CENTAVOS (inteiro) — a formatação
 * para R$ é responsabilidade da UI.
 *
 * Saídas sem `classification` são EXCLUÍDAS do cálculo de % necessidade/desejo
 * (mas continuam contando em "saiu" e no resumo por categoria); o total dessas
 * saídas é exposto em `needs_vs_wants.sem_classificacao` para transparência.
 */
class DashboardService
{
    public function cacheKey(int $userId, string $month): string
    {
        return "dashboard:{$userId}:{$month}";
    }

    /**
     * Retorna os agregados do mês para o usuário, servindo do cache quando disponível.
     *
     * @param  string|null  $month  Mês de referência no formato "aaaa-mm"; default = mês atual.
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
     * Invalida o agregado em cache de um usuário para um mês ("aaaa-mm").
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
     * Calcula os agregados do mês a partir do banco (somas agregadas, sem laço em PHP).
     *
     * @return array<string, mixed>
     */
    private function compute(int $userId, string $month): array
    {
        $start = CarbonImmutable::createFromFormat('Y-m', $month)->startOfMonth();
        $end = $start->endOfMonth();
        $range = [$start->toDateString(), $end->toDateString()];

        $base = fn () => Transaction::query()
            ->where('user_id', $userId)
            ->whereBetween('date', $range);

        // Entrou / Saiu (somas no banco).
        $entrou = (int) $base()->where('type', 'entrada')->sum('amount');
        $saiu = (int) $base()->where('type', 'saida')->sum('amount');

        return [
            'month' => $month,
            'totals' => [
                'entrou' => $entrou,
                'saiu' => $saiu,
                'sobrou' => $entrou - $saiu,
            ],
            'by_category' => $this->byCategory($base),
            'needs_vs_wants' => $this->needsVsWants($base),
        ];
    }

    /**
     * Resumo de saídas por categoria (centavos), com nome/ícone/cor — base do gráfico de rosca.
     *
     * @param  callable(): Builder<Transaction>  $base
     * @return array<int, array<string, mixed>>
     */
    private function byCategory(callable $base): array
    {
        $rows = $base()->where('type', 'saida')
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->get();

        // Carrega as categorias envolvidas em uma única consulta (evita N+1).
        $categories = Category::query()
            ->whereIn('id', $rows->pluck('category_id')->filter()->all())
            ->get()
            ->keyBy('id');

        return $rows->map(function ($row) use ($categories) {
            $category = $row->category_id !== null ? $categories->get($row->category_id) : null;

            return [
                'category_id' => $row->category_id,
                'name' => $category?->name ?? 'Sem categoria',
                'icon' => $category?->icon,
                'color' => $category?->color,
                'total' => (int) $row->total,
            ];
        })->sortByDesc('total')->values()->all();
    }

    /**
     * % Necessidade vs. Desejo sobre as saídas classificadas do mês.
     *
     * @param  callable(): Builder<Transaction>  $base
     * @return array<string, mixed>
     */
    private function needsVsWants(callable $base): array
    {
        $totals = $base()->where('type', 'saida')
            ->whereNotNull('classification')
            ->selectRaw('classification, SUM(amount) as total')
            ->groupBy('classification')
            ->pluck('total', 'classification');

        $necessidade = (int) ($totals['necessidade'] ?? 0);
        $desejo = (int) ($totals['desejo'] ?? 0);
        $classificado = $necessidade + $desejo;

        // Total de saídas sem classificação (excluídas do cálculo de %).
        $semClassificacao = (int) $base()->where('type', 'saida')
            ->whereNull('classification')
            ->sum('amount');

        return [
            'necessidade' => $necessidade,
            'desejo' => $desejo,
            'sem_classificacao' => $semClassificacao,
            'necessidade_pct' => $classificado > 0 ? round($necessidade * 100 / $classificado, 1) : 0.0,
            'desejo_pct' => $classificado > 0 ? round($desejo * 100 / $classificado, 1) : 0.0,
        ];
    }
}
