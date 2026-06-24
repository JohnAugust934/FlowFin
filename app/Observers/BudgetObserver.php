<?php

namespace App\Observers;

use App\Models\Budget;
use App\Services\BudgetService;
use Carbon\CarbonImmutable;

/**
 * Invalida o status de orçamentos em cache quando um orçamento muda.
 *
 * O status é cacheado por usuário/mês; como o limite vale para todo mês, uma
 * mutação de orçamento invalida o mês corrente (meses históricos são recalculados
 * na próxima alteração de transação daquele mês).
 */
class BudgetObserver
{
    public function __construct(private BudgetService $budgets) {}

    public function saved(Budget $budget): void
    {
        $this->budgets->forget($budget->user_id, CarbonImmutable::now()->format('Y-m'));
    }

    public function deleted(Budget $budget): void
    {
        $this->budgets->forget($budget->user_id, CarbonImmutable::now()->format('Y-m'));
    }

    public function restored(Budget $budget): void
    {
        $this->budgets->forget($budget->user_id, CarbonImmutable::now()->format('Y-m'));
    }
}
