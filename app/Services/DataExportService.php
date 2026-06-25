<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Monta o export COMPLETO dos dados do usuário (LGPD — direito de portabilidade).
 *
 * Reúne todas as entidades pessoais do usuário, inclusive registros com soft delete
 * (`withTrashed`), para que o export seja de fato completo. Valores monetários
 * permanecem em CENTAVOS (inteiro) — o contrato JSON documenta isso para a UI 5.4.
 * Campos sensíveis de autenticação (senha, tokens) nunca são exportados.
 */
class DataExportService
{
    /**
     * @return array<string, mixed>
     */
    public function build(User $user): array
    {
        return [
            'exported_at' => CarbonImmutable::now()->toIso8601String(),
            'aviso' => 'Export completo dos seus dados (LGPD). Valores monetários em centavos.',
            'perfil' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'monthly_income' => $user->monthly_income,
                'monthly_savings_goal' => $user->monthly_savings_goal,
                'current_streak' => $user->current_streak,
                'email_verified_at' => optional($user->email_verified_at)->toIso8601String(),
                'created_at' => optional($user->created_at)->toIso8601String(),
            ],
            'categorias' => $user->categories()->withTrashed()->get()->toArray(),
            'transacoes' => $user->transactions()->withTrashed()->get()->toArray(),
            'orcamentos' => $user->budgets()->withTrashed()->get()->toArray(),
            'metas' => $user->goals()->withTrashed()->get()->toArray(),
            'investimentos' => $user->investments()->withTrashed()->get()->toArray(),
            'recorrencias' => $user->recurrences()->withTrashed()->get()->toArray(),
        ];
    }
}
