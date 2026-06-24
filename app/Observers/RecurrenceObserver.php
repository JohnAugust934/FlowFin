<?php

namespace App\Observers;

use App\Models\Recurrence;
use App\Services\InsightsService;

/**
 * Invalida o cache de gastos "invisíveis" sempre que uma recorrência muda.
 *
 * O detector de invisíveis soma o impacto mensal das recorrências de saída ativas;
 * qualquer criação, edição, (des)ativação ou exclusão altera esse total.
 */
class RecurrenceObserver
{
    public function __construct(private InsightsService $insights) {}

    public function created(Recurrence $recurrence): void
    {
        $this->insights->forgetInvisible($recurrence->user_id);
    }

    public function updated(Recurrence $recurrence): void
    {
        $this->insights->forgetInvisible($recurrence->user_id);
    }

    public function deleted(Recurrence $recurrence): void
    {
        $this->insights->forgetInvisible($recurrence->user_id);
    }

    public function restored(Recurrence $recurrence): void
    {
        $this->insights->forgetInvisible($recurrence->user_id);
    }

    public function forceDeleted(Recurrence $recurrence): void
    {
        $this->insights->forgetInvisible($recurrence->user_id);
    }
}
