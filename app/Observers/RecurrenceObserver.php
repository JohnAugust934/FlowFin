<?php

namespace App\Observers;

use App\Models\Recurrence;
use App\Services\InsightsService;
use App\Services\SavingsReportService;
use Carbon\CarbonImmutable;

/**
 * Invalida os caches dependentes de recorrências sempre que uma recorrência muda.
 *
 * - Gastos "invisíveis": o detector soma o impacto mensal das recorrências de saída
 *   ativas; qualquer criação, edição, (des)ativação ou exclusão altera esse total.
 * - Relatório "Onde economizar": a parte de gastos recorrentes depende das recorrências.
 *   Como o relatório é cacheado por mês mas a contribuição das recorrências é a mesma em
 *   todos os meses, invalidamos apenas o mês corrente (limitação aceita: relatórios de
 *   meses passados se recalculam na próxima alteração de transação daquele mês).
 */
class RecurrenceObserver
{
    public function __construct(
        private InsightsService $insights,
        private SavingsReportService $savingsReport,
    ) {}

    public function created(Recurrence $recurrence): void
    {
        $this->forget($recurrence);
    }

    public function updated(Recurrence $recurrence): void
    {
        $this->forget($recurrence);
    }

    public function deleted(Recurrence $recurrence): void
    {
        $this->forget($recurrence);
    }

    public function restored(Recurrence $recurrence): void
    {
        $this->forget($recurrence);
    }

    public function forceDeleted(Recurrence $recurrence): void
    {
        $this->forget($recurrence);
    }

    private function forget(Recurrence $recurrence): void
    {
        $this->insights->forgetInvisible($recurrence->user_id);
        $this->savingsReport->forget($recurrence->user_id, CarbonImmutable::now()->format('Y-m'));
    }
}
