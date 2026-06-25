<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use App\Support\Money;
use Carbon\CarbonImmutable;

/**
 * Monta os dados do relatório mensal do usuário para export (CSV/PDF).
 *
 * Todos os valores permanecem em CENTAVOS internamente; a formatação para R$
 * (formato brasileiro) e datas (dd/mm/aaaa) acontece apenas na apresentação,
 * via o helper {@see Money}. Sempre escopado ao usuário autenticado.
 */
class MonthlyReportService
{
    public function __construct(private DashboardService $dashboard) {}

    /**
     * Constrói o relatório do mês ("aaaa-mm"; default = mês atual).
     *
     * @return array{
     *   month: string,
     *   month_label: string,
     *   user_name: string,
     *   generated_at: string,
     *   totals: array{entrou: int, saiu: int, sobrou: int},
     *   rows: array<int, array{date: string, type: string, category: string, description: string, classification: string, amount: int}>
     * }
     */
    public function build(User $user, ?string $month = null): array
    {
        $month = $this->dashboard->normalizeMonth($month);
        $start = CarbonImmutable::createFromFormat('Y-m', $month)->startOfMonth();
        $end = $start->endOfMonth();

        // Eager loading da categoria para evitar N+1 ao montar as linhas.
        $transactions = Transaction::query()
            ->with('category:id,name')
            ->where('user_id', $user->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $rows = $transactions->map(fn (Transaction $t) => [
            'date' => $t->date->format('d/m/Y'),
            'type' => $t->type === 'entrada' ? 'Entrada' : 'Saída',
            'category' => $t->category?->name ?? 'Sem categoria',
            'description' => $t->description ?? '',
            'classification' => match ($t->classification) {
                'necessidade' => 'Necessidade',
                'desejo' => 'Desejo',
                default => '',
            },
            'amount' => (int) $t->amount,
        ])->all();

        $agg = $this->dashboard->forUser($user, $month);

        return [
            'month' => $month,
            'month_label' => $start->locale('pt_BR')->isoFormat('MMMM [de] YYYY'),
            'user_name' => $user->name,
            'generated_at' => CarbonImmutable::now()->format('d/m/Y H:i'),
            'totals' => [
                'entrou' => (int) $agg['totals']['entrou'],
                'saiu' => (int) $agg['totals']['saiu'],
                'sobrou' => (int) $agg['totals']['sobrou'],
            ],
            'rows' => $rows,
        ];
    }
}
