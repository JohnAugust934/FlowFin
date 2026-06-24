<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSavingsGoalRequest;
use App\Services\SavingsGoalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API JSON da meta de economia mensal (Pilar 3). Autenticada por sessão, escopada ao usuário.
 *
 * Valores em CENTAVOS inteiros.
 *  - GET /api/savings-goal?month=aaaa-mm → progresso da meta no mês:
 *      { month, goal, saved, progress_pct, achieved }
 *        - goal: meta definida (null se não definida)
 *        - saved: "sobrou" do mês (entrou − saiu)
 *        - progress_pct: % da meta coberto pelo "sobrou" (0–100; null se sem meta)
 *  - PUT /api/savings-goal → define a meta; corpo: { monthly_savings_goal } (null limpa a meta).
 */
class SavingsGoalController extends Controller
{
    public function show(Request $request, SavingsGoalService $service): JsonResponse
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        return response()->json($service->forUser($request->user(), $validated['month'] ?? null));
    }

    public function update(UpdateSavingsGoalRequest $request, SavingsGoalService $service): JsonResponse
    {
        $user = $request->user();
        $user->update(['monthly_savings_goal' => $request->validated()['monthly_savings_goal']]);

        return response()->json($service->forUser($user->fresh()));
    }
}
