<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SavingsReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API JSON do relatório "Onde economizar" (Pilar 3 — Economia).
 * Autenticada por sessão, escopada ao usuário. Valores em CENTAVOS inteiros.
 *
 * Sugestões determinísticas (sem ML) de onde cortar gastos: maiores categorias de
 * saída classificadas como "Desejo" + gastos recorrentes, com corte percentual e
 * economia estimada, priorizadas pela maior economia potencial.
 *
 *  - GET /api/savings-report?month=aaaa-mm  → (default = mês atual)
 *      {
 *        "month": "2026-06",
 *        "total_potential_savings": 12300,            // soma das economias estimadas (centavos)
 *        "count": 3,
 *        "suggestions": [
 *          {
 *            "type": "categoria_desejo" | "recorrente",
 *            "reference_id": 7,                         // id da categoria (desejo) ou da recorrência
 *            "label": "Lazer",
 *            "current_amount": 30000,                   // gasto atual no mês / impacto mensal (centavos)
 *            "cut_pct": 30,                             // 30% (desejo) | 20% (recorrente)
 *            "estimated_savings": 9000,                 // economia mensal estimada (centavos)
 *            "category": { id, name, icon, color } | null,
 *            "message": "Reduza 30% em Lazer e economize R$ 90,00 por mês."
 *          }
 *        ]
 *      }
 */
class SavingsReportController extends Controller
{
    public function index(Request $request, SavingsReportService $service): JsonResponse
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        return response()->json($service->forUser($request->user(), $validated['month'] ?? null));
    }
}
