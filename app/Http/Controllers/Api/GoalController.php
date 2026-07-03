<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGoalRequest;
use App\Http\Requests\UpdateGoalRequest;
use App\Http\Resources\GoalResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * API JSON de metas com propósito (Pilar 5). Autenticada por sessão, escopada ao
 * usuário, soft delete. Valores em CENTAVOS inteiros.
 *
 *  - GET    /api/goals?page=  → lista paginada (20/página), ORDENADA POR PRIORIDADE
 *      (alta → media → baixa) e depois pelo prazo (mais próximo primeiro). Cada item:
 *      { id, name, description, target_amount, saved_amount, progress_pct,
 *        remaining_amount, due_date (aaaa-mm-dd|null), priority, created_at, updated_at }
 *  - POST   /api/goals        → cria; corpo: { name, description?, target_amount,
 *      saved_amount?, due_date?, priority? ('baixa'|'media'|'alta', default 'media') }.
 *  - GET    /api/goals/{id}   → detalhe de uma meta do usuário.
 *  - PUT    /api/goals/{id}   → atualiza (parcial).
 *  - DELETE /api/goals/{id}   → exclui (soft delete).
 */
class GoalController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $goals = $request->user()
            ->goals()
            ->orderByRaw("CASE priority WHEN 'alta' THEN 0 WHEN 'media' THEN 1 ELSE 2 END")
            ->orderByRaw('due_date IS NULL') // metas com prazo antes das sem prazo
            ->orderBy('due_date')
            ->orderByDesc('id')
            ->paginate(20);

        return GoalResource::collection($goals);
    }

    public function store(StoreGoalRequest $request): JsonResponse
    {
        $goal = $request->user()->goals()->create($request->validated());

        return GoalResource::make($goal)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, int $id): GoalResource
    {
        $goal = $request->user()->goals()->findOrFail($id);

        return GoalResource::make($goal);
    }

    public function update(UpdateGoalRequest $request, int $id): GoalResource
    {
        $goal = $request->user()->goals()->findOrFail($id);

        $goal->update($request->validated());

        return GoalResource::make($goal);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $goal = $request->user()->goals()->findOrFail($id);

        $goal->delete(); // soft delete

        return response()->json(['message' => 'Meta excluída com sucesso.']);
    }
}
