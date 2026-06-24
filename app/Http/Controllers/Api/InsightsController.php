<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\InsightsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API JSON dos insights de "Consciência" (Pilar 2). Autenticada por sessão, escopada ao usuário.
 *
 * Valores em CENTAVOS inteiros; datas "aaaa-mm-dd".
 *
 *  - GET /api/insights?month=aaaa-mm  → agregados do mês (default = mês atual):
 *      {
 *        "month": "2026-06",
 *        "top_expenses": [ { id, description, amount, date, category } ],   // 3 maiores saídas
 *        "daily_timeline": [ { date, total } ],                            // todos os dias do mês
 *        "month_comparison": {
 *           "current":  { entrou, saiu, sobrou },
 *           "previous": { month, entrou, saiu, sobrou },
 *           "variation": { entrou_pct, saiu_pct, sobrou_pct }              // pct null se base 0
 *        }
 *      }
 *  - GET /api/insights/invisible  → gastos "invisíveis" (recorrências de saída ativas):
 *      { count, total_monthly_impact, items: [ { id, description, amount, frequency, monthly_impact, category } ] }
 */
class InsightsController extends Controller
{
    public function index(Request $request, InsightsService $service): JsonResponse
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        return response()->json($service->forUser($request->user(), $validated['month'] ?? null));
    }

    public function invisible(Request $request, InsightsService $service): JsonResponse
    {
        return response()->json($service->invisibleSpending($request->user()));
    }
}
