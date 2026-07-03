<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ScoreService;
use App\Services\StreakService;
use App\Services\TipsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API JSON de gamificação e direcionamento (Pilar 4 — Mentalidade). Autenticada por
 * sessão, escopada ao usuário. Valores monetários em CENTAVOS inteiros; `month=aaaa-mm`.
 *
 *  - GET /api/score?month=aaaa-mm → Score FlowFin do mês:
 *      { month, score (0–100), factors: {
 *          consistency:  { value, weight, included },
 *          budgets:      { value|null, weight, included },
 *          savings_goal: { value|null, weight, included } } }
 *      `value` é o fator normalizado 0–100; `included=false` indica fator excluído
 *      (sem orçamento / sem meta) — nesse caso os pesos são reescalados para somar 100.
 *
 *  - GET /api/streak → sequência de dias com registro:
 *      { current_streak, last_activity_date (aaaa-mm-dd|null), active }
 *
 *  - GET /api/tips?month=aaaa-mm → dicas contextuais determinísticas (PT-BR):
 *      [ { code, level ('alerta'|'positivo'|'educativo'), title, message, theme } ]
 */
class GamificationController extends Controller
{
    public function score(Request $request, ScoreService $service): JsonResponse
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        return response()->json($service->forUser($request->user(), $validated['month'] ?? null));
    }

    public function streak(Request $request, StreakService $service): JsonResponse
    {
        return response()->json($service->compute($request->user()));
    }

    public function tips(Request $request, TipsService $service): JsonResponse
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        return response()->json($service->forUser($request->user(), $validated['month'] ?? null));
    }
}
