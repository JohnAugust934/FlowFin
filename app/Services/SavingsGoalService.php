<?php

namespace App\Services;

use App\Models\User;

/**
 * Meta de economia mensal e seu progresso.
 *
 * A meta é "quanto o usuário pretende sobrar no mês" (centavos). O progresso é
 * o quanto do "sobrou" do mês já cobre essa meta. Deriva os totais do mês do
 * DashboardService (reaproveitando o cache de agregados já existente).
 */
class SavingsGoalService
{
    public function __construct(private DashboardService $dashboard) {}

    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user, ?string $month = null): array
    {
        $month = $this->dashboard->normalizeMonth($month);
        $totals = $this->dashboard->forUser($user, $month)['totals'];
        $sobrou = (int) $totals['sobrou'];

        $goal = $user->monthly_savings_goal !== null ? (int) $user->monthly_savings_goal : null;

        $progressPct = null;
        $achieved = false;
        if ($goal !== null && $goal > 0) {
            $progressPct = round(max(0, $sobrou) * 100 / $goal, 1);
            $progressPct = min(100.0, $progressPct);
            $achieved = $sobrou >= $goal;
        }

        return [
            'month' => $month,
            'goal' => $goal,
            'saved' => $sobrou,            // o "sobrou" do mês (centavos)
            'progress_pct' => $progressPct, // null quando não há meta definida
            'achieved' => $achieved,
        ];
    }
}
