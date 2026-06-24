<?php

namespace App\Services;

use App\Models\Recurrence;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Projeção das contas fixas (recorrências) para um mês de referência.
 *
 * Para cada recorrência ativa, calcula as ocorrências que caem dentro do mês
 * (conforme a frequência e a `start_date`), com as datas previstas e o total
 * projetado de entradas e saídas do mês. Valores em CENTAVOS (inteiro).
 *
 * Cálculo barato e dependente de "hoje"/parâmetro de mês — não é cacheado.
 */
class RecurrenceProjectionService
{
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
    public function forUser(User $user, ?string $month = null): array
    {
        $month = $this->normalizeMonth($month);
        $start = CarbonImmutable::createFromFormat('Y-m', $month)->startOfMonth();
        $end = $start->endOfMonth();

        $recurrences = Recurrence::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->with('category')
            ->orderBy('description')
            ->get();

        $items = [];
        $totalEntrada = 0;
        $totalSaida = 0;

        foreach ($recurrences as $recurrence) {
            $occurrences = $this->occurrencesInMonth($recurrence, $start, $end);

            if ($occurrences === []) {
                continue;
            }

            $amount = (int) $recurrence->amount;
            $monthTotal = $amount * count($occurrences);

            if ($recurrence->type === 'entrada') {
                $totalEntrada += $monthTotal;
            } else {
                $totalSaida += $monthTotal;
            }

            $items[] = [
                'id' => $recurrence->id,
                'description' => $recurrence->description,
                'type' => $recurrence->type,
                'amount' => $amount,
                'frequency' => $recurrence->frequency,
                'occurrences' => $occurrences,
                'month_total' => $monthTotal,
                'category' => $recurrence->category ? [
                    'id' => $recurrence->category->id,
                    'name' => $recurrence->category->name,
                    'icon' => $recurrence->category->icon,
                    'color' => $recurrence->category->color,
                ] : null,
            ];
        }

        return [
            'month' => $month,
            'totals' => [
                'entrada' => $totalEntrada,
                'saida' => $totalSaida,
                'saldo' => $totalEntrada - $totalSaida,
            ],
            'items' => $items,
        ];
    }

    /**
     * Datas ("aaaa-mm-dd") em que a recorrência incide dentro do intervalo do mês.
     *
     * @return array<int, string>
     */
    private function occurrencesInMonth(Recurrence $recurrence, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $anchor = CarbonImmutable::parse($recurrence->start_date->toDateString());

        // Recorrência que ainda não começou no mês de referência não incide.
        if ($anchor->greaterThan($end)) {
            return [];
        }

        $step = match ($recurrence->frequency) {
            'diaria' => '1 day',
            'semanal' => '1 week',
            'anual' => '1 year',
            default => '1 month',
        };

        $dates = [];
        // Avança a partir da âncora até alcançar o início do intervalo, depois coleta até o fim.
        for ($date = $anchor; $date->lessThanOrEqualTo($end); $date = $date->add($step)) {
            if ($date->greaterThanOrEqualTo($start)) {
                $dates[] = $date->format('Y-m-d');
            }
        }

        return $dates;
    }
}
