<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API JSON dos agregados do dashboard (autenticada por sessão).
 *
 * Contrato de payload (consumido pela UI do dashboard — Task 2.4):
 *  - GET /api/dashboard?month=aaaa-mm  → agregados do mês (default = mês atual).
 *
 * Resposta (todos os valores em CENTAVOS inteiros):
 *  {
 *    "month": "2026-06",
 *    "totals": { "entrou": int, "saiu": int, "sobrou": int },
 *    "by_category": [ { "category_id": int|null, "name": str, "icon": str|null, "color": str|null, "total": int } ],
 *    "needs_vs_wants": { "necessidade": int, "desejo": int, "sem_classificacao": int,
 *                        "necessidade_pct": float, "desejo_pct": float }
 *  }
 *
 * Escopado ao usuário autenticado. Exige header `Accept: application/json`.
 */
class DashboardController extends Controller
{
    public function index(Request $request, DashboardService $service): JsonResponse
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $data = $service->forUser($request->user(), $validated['month'] ?? null);

        return response()->json($data);
    }
}
