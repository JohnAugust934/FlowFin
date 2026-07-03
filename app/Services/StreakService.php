<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Sequência ("streak") de dias consecutivos com pelo menos um registro de transação.
 *
 * Regra (Spec): conta dias consecutivos com ≥1 registro; reseta quando um dia passa
 * sem registro. O dia corrente conta como "em aberto": enquanto não houver registro
 * hoje, a sequência construída até ontem permanece válida (não quebra no meio do dia).
 * Assim que o dia de hoje terminar sem registro, o próximo cálculo devolve a sequência
 * que termina ontem; se também ontem não houver registro, a sequência é zero.
 *
 * O valor é DETERMINÍSTICO e recalculado a partir das transações. O scheduler mantém
 * um snapshot em `users.current_streak` para exibição rápida e para materializar o
 * "reset" diário (ver RecalculateStreaks).
 */
class StreakService
{
    /**
     * Calcula a sequência atual de dias do usuário relativa a uma data de referência
     * (default = hoje). Não persiste nada.
     *
     * @return array{current_streak: int, last_activity_date: string|null, active: bool}
     */
    public function compute(User $user, ?CarbonImmutable $reference = null): array
    {
        $today = ($reference ?? CarbonImmutable::now())->startOfDay();

        // Conjunto de dias (aaaa-mm-dd) com pelo menos um registro de saída/entrada.
        $days = Transaction::query()
            ->where('user_id', $user->id)
            ->selectRaw('date')
            ->distinct()
            ->pluck('date')
            ->map(fn ($d) => CarbonImmutable::parse((string) $d)->format('Y-m-d'))
            ->flip();

        if ($days->isEmpty()) {
            return ['current_streak' => 0, 'last_activity_date' => null, 'active' => false];
        }

        $lastActivity = $days->keys()->sort()->last();

        // Ponto de partida: hoje, se houver registro hoje; senão ontem (dia em aberto).
        $cursor = $days->has($today->format('Y-m-d')) ? $today : $today->subDay();

        $streak = 0;
        while ($days->has($cursor->format('Y-m-d'))) {
            $streak++;
            $cursor = $cursor->subDay();
        }

        return [
            'current_streak' => $streak,
            'last_activity_date' => $lastActivity,
            'active' => $streak > 0,
        ];
    }

    /**
     * Recalcula e persiste o snapshot de sequência do usuário (usado pelo scheduler).
     */
    public function refresh(User $user, ?CarbonImmutable $reference = null): int
    {
        $streak = $this->compute($user, $reference)['current_streak'];

        if ((int) $user->current_streak !== $streak) {
            $user->forceFill(['current_streak' => $streak])->save();
        }

        return $streak;
    }
}
