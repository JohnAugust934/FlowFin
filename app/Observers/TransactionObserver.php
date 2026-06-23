<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Services\DashboardService;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Invalida os agregados do dashboard em cache sempre que uma transação muda.
 *
 * Cobre todos os caminhos de mutação (criação, edição, exclusão soft, restauração
 * e exclusão definitiva). Quando a edição move a transação entre meses, ambos os
 * meses afetados são invalidados.
 */
class TransactionObserver
{
    public function __construct(private DashboardService $dashboard) {}

    public function created(Transaction $transaction): void
    {
        $this->forget($transaction);
    }

    public function updated(Transaction $transaction): void
    {
        $this->forget($transaction);

        // Se a data mudou de mês, invalida também o mês de origem.
        if ($transaction->wasChanged('date')) {
            $this->dashboard->forget(
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
        $this->dashboard->forget($transaction->user_id, $this->monthOf($transaction->date));
    }

    private function monthOf(CarbonInterface|string $date): string
    {
        return $date instanceof CarbonInterface
            ? $date->format('Y-m')
            : Carbon::parse($date)->format('Y-m');
    }
}
