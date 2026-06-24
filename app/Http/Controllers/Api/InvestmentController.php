<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvestmentRequest;
use App\Http\Requests\UpdateInvestmentRequest;
use App\Http\Resources\InvestmentResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API JSON de investimentos (registro simplificado, Pilar 5). Autenticada por sessão,
 * escopada ao usuário, soft delete. Valores em CENTAVOS inteiros.
 *
 *  - GET    /api/investments?page= → lista paginada (20/página) + TOTAL agregado:
 *      { data: [ { id, description, type, amount, created_at, updated_at } ],
 *        meta: { ..paginação.. }, links: {...}, total_invested }
 *      `total_invested` (chave de topo) é a soma (centavos) de TODOS os investimentos
 *      ativos do usuário, não apenas da página corrente.
 *  - POST   /api/investments       → cria; corpo: { description, type?, amount }.
 *  - PUT    /api/investments/{id}   → atualiza (parcial).
 *  - DELETE /api/investments/{id}   → exclui (soft delete).
 */
class InvestmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $investments = $request->user()
            ->investments()
            ->orderByDesc('id')
            ->paginate(20);

        // Total agregado sobre TODOS os investimentos do usuário (não só a página).
        $total = (int) $request->user()->investments()->sum('amount');

        return InvestmentResource::collection($investments)
            ->additional(['total_invested' => $total])
            ->response();
    }

    public function store(StoreInvestmentRequest $request): JsonResponse
    {
        $investment = $request->user()->investments()->create($request->validated());

        return InvestmentResource::make($investment)
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateInvestmentRequest $request, int $id): InvestmentResource
    {
        $investment = $request->user()->investments()->findOrFail($id);

        $investment->update($request->validated());

        return InvestmentResource::make($investment);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $investment = $request->user()->investments()->findOrFail($id);

        $investment->delete(); // soft delete

        return response()->json(['message' => 'Investimento excluído com sucesso.']);
    }
}
