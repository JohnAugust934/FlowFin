<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexTransactionRequest;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Http\Resources\TransactionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * API JSON de transações (autenticada por sessão).
 *
 * Contrato de payload (consumido pela UI e pela camada offline):
 *  - GET    /api/transactions          → lista paginada (20/página), com a categoria embutida.
 *  - POST   /api/transactions          → cria; corpo: { type, amount(centavos), category_id, date, description?, classification?, is_recurring? }
 *  - GET    /api/transactions/{id}     → mostra uma transação do usuário.
 *  - PUT    /api/transactions/{id}     → atualiza (representação completa).
 *  - DELETE /api/transactions/{id}     → exclui (soft delete).
 *
 * `amount` trafega SEMPRE em centavos (inteiro), na entrada e na saída.
 * Todas as operações são escopadas ao usuário autenticado: um usuário nunca
 * acessa transações de outro (consultas via $request->user()->transactions()).
 */
class TransactionController extends Controller
{
    public function index(IndexTransactionRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();

        $transactions = $request->user()
            ->transactions()
            ->with('category')          // eager loading: evita N+1 ao exibir a categoria
            // Filtros server-side (todos opcionais), aplicados sobre a query
            // já escopada por usuário. A paginação reflete o total filtrado.
            ->when(
                $filters['date_from'] ?? null,
                fn ($query, $dateFrom) => $query->whereDate('date', '>=', $dateFrom)
            )
            ->when(
                $filters['date_to'] ?? null,
                fn ($query, $dateTo) => $query->whereDate('date', '<=', $dateTo)
            )
            ->when(
                $filters['category_id'] ?? null,
                fn ($query, $categoryId) => $query->where('category_id', $categoryId)
            )
            ->when(
                $filters['type'] ?? null,
                fn ($query, $type) => $query->where('type', $type)
            )
            ->latest('date')
            ->latest('id')
            ->paginate(20)              // pagina em 20 itens por página
            ->withQueryString();        // preserva os filtros nos links de paginação

        return TransactionResource::collection($transactions);
    }

    public function store(StoreTransactionRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Classificação só faz sentido para saídas.
        if (($data['type'] ?? null) === 'entrada') {
            $data['classification'] = null;
        }

        $transaction = $request->user()->transactions()->create($data);

        return TransactionResource::make($transaction->load('category'))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, int $id): TransactionResource
    {
        $transaction = $request->user()
            ->transactions()
            ->with('category')
            ->findOrFail($id);

        return TransactionResource::make($transaction);
    }

    public function update(UpdateTransactionRequest $request, int $id): TransactionResource
    {
        $transaction = $request->user()->transactions()->findOrFail($id);

        $data = $request->validated();

        if (($data['type'] ?? null) === 'entrada') {
            $data['classification'] = null;
        }

        $transaction->update($data);

        return TransactionResource::make($transaction->load('category'));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $transaction = $request->user()->transactions()->findOrFail($id);

        $transaction->delete();         // soft delete

        return response()->json(['message' => 'Transação excluída com sucesso.']);
    }
}
