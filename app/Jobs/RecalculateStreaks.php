<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\StreakService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Recalcula e materializa a sequência ("streak") de cada usuário.
 *
 * Rodada diariamente pelo scheduler do Laravel (cron único, sem worker dedicado —
 * a fila é no banco, QUEUE_CONNECTION=database). É o que efetiva o RESET: um usuário
 * que passou o dia anterior sem registrar tem a sequência zerada no snapshot.
 *
 * Processa em lotes (chunkById) para não carregar todos os usuários de uma vez,
 * respeitando a hospedagem compartilhada.
 */
class RecalculateStreaks implements ShouldQueue
{
    use Queueable;

    public function handle(StreakService $streaks): void
    {
        User::query()->chunkById(200, function ($users) use ($streaks) {
            foreach ($users as $user) {
                $streaks->refresh($user);
            }
        });
    }
}
