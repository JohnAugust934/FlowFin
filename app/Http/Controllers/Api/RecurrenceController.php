<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRecurrenceRequest;
use App\Http\Requests\UpdateRecurrenceRequest;
use App\Http\Resources\RecurrenceResource;
use App\Services\RecurrenceProjectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * API JSON de recorrências / contas fixas (autenticada por sessão, escopada ao usuário).
 *
 * Contrato de payload (valores em CENTAVOS inteiros; datas "aaaa-mm-dd"):
 *  - GET    /api/recurrences              → lista paginada (20/página) das recorrências do usuário.
 *  - POST   /api/recurrences              → cria; corpo: { description, type, amount, frequency, start_date, category_id?, next_due_date?, is_active? }
 *  - GET    /api/recurrences/{id}         → detalhe de uma recorrência do usuário.
 *  - PUT    /api/recurrences/{id}         → atualiza.
 *  - DELETE /api/recurrences/{id}         → exclui (soft delete).
 *  - GET    /api/recurrences/projection?month=aaaa-mm → projeção das contas fixas do mês
 *      → { month, totals: { entrada, saida, saldo }, items: [ { id, description, type, amount,
 *           frequency, occurrences: [aaaa-mm-dd], month_total, category } ] }
 */
class RecurrenceController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $recurrences = $request->user()
            ->recurrences()
            ->with('category')
            ->orderByDesc('is_active')
            ->orderBy('description')
            ->paginate(20)
            ->withQueryString();

        return RecurrenceResource::collection($recurrences);
    }

    public function store(StoreRecurrenceRequest $request): JsonResponse
    {
        $recurrence = $request->user()->recurrences()->create($request->validated());

        return RecurrenceResource::make($recurrence->load('category'))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, int $id): RecurrenceResource
    {
        $recurrence = $request->user()->recurrences()->with('category')->findOrFail($id);

        return RecurrenceResource::make($recurrence);
    }

    public function update(UpdateRecurrenceRequest $request, int $id): RecurrenceResource
    {
        $recurrence = $request->user()->recurrences()->findOrFail($id);

        $recurrence->update($request->validated());

        return RecurrenceResource::make($recurrence->load('category'));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $recurrence = $request->user()->recurrences()->findOrFail($id);

        $recurrence->delete(); // soft delete

        return response()->json(['message' => 'Conta fixa excluída com sucesso.']);
    }

    public function projection(Request $request, RecurrenceProjectionService $service): JsonResponse
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        return response()->json($service->forUser($request->user(), $validated['month'] ?? null));
    }
}
