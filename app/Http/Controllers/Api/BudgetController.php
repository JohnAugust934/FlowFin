<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBudgetRequest;
use App\Http\Requests\UpdateBudgetRequest;
use App\Http\Resources\BudgetResource;
use App\Services\BudgetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * API JSON de orçamentos por categoria (Pilar 3). Autenticada por sessão, escopada ao usuário.
 *
 * Valores em CENTAVOS inteiros.
 *  - GET    /api/budgets                 → lista os orçamentos do usuário (conjunto pequeno; não paginado).
 *  - POST   /api/budgets                 → cria; corpo: { category_id, monthly_limit }. Um por categoria.
 *  - PUT    /api/budgets/{id}            → atualiza o limite mensal.
 *  - DELETE /api/budgets/{id}            → exclui (soft delete).
 *  - GET    /api/budgets/status?month=aaaa-mm → status semafórico por orçamento no mês:
 *      [ { id, category, monthly_limit, consumed, remaining, percentage, status } ]
 *        status ∈ { "ok" (<80%), "alerta" (80–99%), "estourado" (≥100%) }
 */
class BudgetController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $budgets = $request->user()
            ->budgets()
            ->with('category')
            ->get();

        return BudgetResource::collection($budgets);
    }

    public function store(StoreBudgetRequest $request): JsonResponse
    {
        $budget = $request->user()->budgets()->create($request->validated());

        return BudgetResource::make($budget->load('category'))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateBudgetRequest $request, int $id): BudgetResource
    {
        $budget = $request->user()->budgets()->findOrFail($id);

        $budget->update($request->validated());

        return BudgetResource::make($budget->load('category'));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $budget = $request->user()->budgets()->findOrFail($id);

        $budget->delete(); // soft delete

        return response()->json(['message' => 'Orçamento excluído com sucesso.']);
    }

    public function status(Request $request, BudgetService $service): JsonResponse
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        return response()->json($service->statusForUser($request->user(), $validated['month'] ?? null));
    }
}
