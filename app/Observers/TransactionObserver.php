<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Services\BudgetService;
use App\Services\DashboardService;
use App\Services\InsightsService;
use App\Services\SavingsReportService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Invalida os agregados em cache (dashboard, consciência e orçamentos) sempre que
 * uma transação muda.
 *
 * Cobre todos os caminhos de mutação (criação, edição, exclusão soft, restauração
 * e exclusão definitiva). Quando a edição move a transação entre meses, ambos os
 * meses afetados são invalidados.
 *
 * O comparativo mês a mês de um mês M lê também o mês M-1; por isso, ao invalidar
 * o mês de uma transação, invalidamos também os insights do mês seguinte (cujo
 * comparativo depende deste mês).
 */
class TransactionObserver
{
    public function __construct(
        private DashboardService $dashboard,
        private InsightsService $insights,
        private BudgetService $budgets,
        private SavingsReportService $savingsReport,
    ) {}

    public function created(Transaction $transaction): void
    {
        $this->forget($transaction);
    }

    public function updated(Transaction $transaction): void
    {
        $this->forget($transaction);

        // Se a data mudou de mês, invalida também o mês de origem.
        if ($transaction->wasChanged('date')) {
            $this->forgetMonth(
                $transaction->user_id,
                $this->monthOf($transaction->getOriginal('date')),
            );
        }
    }

    public function deleted(Transaction $transaction): void
    {
        $this->forget($transaction);
    }

    public function restored(Transaction $transaction): void
    {
        $this->forget($transaction);
    }

    public function forceDeleted(Transaction $transaction): void
    {
        $this->forget($transaction);
    }

    private function forget(Transaction $transaction): void
    {
        $this->forgetMonth($transaction->user_id, $this->monthOf($transaction->date));
    }

    private function forgetMonth(int $userId, string $month): void
    {
        $this->dashboard->forget($userId, $month);
        $this->insights->forget($userId, $month);
        $this->budgets->forget($userId, $month);
        $this->savingsReport->forget($userId, $month);

        // O comparativo do mês seguinte depende deste mês.
        $nextMonth = CarbonImmutable::createFromFormat('Y-m', $month)->addMonth()->format('Y-m');
        $this->insights->forget($userId, $nextMonth);
    }

    private function monthOf(CarbonInterface|string $date): string
    {
        return $date instanceof CarbonInterface
            ? $date->format('Y-m')
            : Carbon::parse($date)->format('Y-m');
    }
}
