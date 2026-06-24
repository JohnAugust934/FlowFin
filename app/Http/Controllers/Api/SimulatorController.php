<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SimulateGoalRequest;
use App\Services\SimulatorService;
use Illuminate\Http\JsonResponse;

/**
 * API JSON do simulador de metas (Pilar 5). Autenticada por sessão.
 *
 *  - POST /api/goals/simulate → dados DOIS dos três campos, calcula o terceiro.
 *      Corpo: { monthly_amount?, target_amount?, months? } (centavos / meses).
 *      Resposta: { monthly_amount, target_amount, months, computed }
 *        `computed` indica qual campo foi calculado ('months'|'target_amount'|'monthly_amount').
 *      Fórmulas: meses = ceil(alvo / mensal); alvo = mensal × meses; mensal = ceil(alvo / meses).
 */
class SimulatorController extends Controller
{
    public function simulate(SimulateGoalRequest $request, SimulatorService $service): JsonResponse
    {
        $data = $request->validated();

        return response()->json($service->simulate(
            $data['monthly_amount'] ?? null,
            $data['target_amount'] ?? null,
            $data['months'] ?? null,
        ));
    }
}
