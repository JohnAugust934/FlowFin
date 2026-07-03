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

    public function totalsCacheKey(int $userId, string $month): string
    {
        return "dashboard:totals:{$userId}:{$month}";
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

        $data = Cache::rememberForever(
            $this->cacheKey($user->id, $month),
            fn () => $this->compute($user->id, $month),
        );

        // A série histórica cruza meses; fica fora do cache do payload mensal e
        // se apoia nos totais por mês (cada um cacheado e invalidado por si).
        $data['history'] = $this->history($user->id, $month);

        return $data;
    }

    /**
     * Série "entrou/saiu" dos últimos 6 meses (incluindo o de referência),
     * em ordem cronológica — base do gráfico de linha do dashboard.
     *
     * @return array<int, array<string, mixed>>
     */
    public function history(int $userId, string $month, int $months = 6): array
    {
        $ref = CarbonImmutable::createFromFormat('Y-m', $month)->startOfMonth();

        $wanted = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $wanted[] = $ref->subMonths($i)->format('Y-m');
        }

        // Serve do cache o que der; os meses ausentes saem em UMA query agrupada.
        $totals = [];
        $missing = [];
        foreach ($wanted as $m) {
            $cached = Cache::get($this->totalsCacheKey($userId, $m));
            if ($cached !== null) {
                $totals[$m] = $cached;
            } else {
                $missing[] = $m;
            }
        }

        if ($missing !== []) {
            $start = CarbonImmutable::createFromFormat('Y-m', min($missing))->startOfMonth();
            $end = CarbonImmutable::createFromFormat('Y-m', max($missing))->endOfMonth();

            $rows = Transaction::query()
                ->where('user_id', $userId)
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->selectRaw("substr(date, 1, 7) as ym, type, SUM(amount) as total")
                ->groupBy('ym', 'type')
                ->get();

            foreach ($missing as $m) {
                $totals[$m] = [
                    'entrou' => (int) $rows->first(fn ($r) => $r->ym === $m && $r->type === 'entrada')?->total,
                    'saiu' => (int) $rows->first(fn ($r) => $r->ym === $m && $r->type === 'saida')?->total,
                ];
                Cache::forever($this->totalsCacheKey($userId, $m), $totals[$m]);
            }
        }

        return array_map(fn (string $m) => [
            'month' => $m,
            'entrou' => $totals[$m]['entrou'],
            'saiu' => $totals[$m]['saiu'],
            'sobrou' => $totals[$m]['entrou'] - $totals[$m]['saiu'],
        ], $wanted);
    }

    /**
     * Invalida o agregado em cache de um usuário para um mês ("aaaa-mm").
     */
    public function forget(int $userId, string $month): void
    {
        Cache::forget($this->cacheKey($userId, $month));
        Cache::forget($this->totalsCacheKey($userId, $month));
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
