<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * API JSON de categorias (autenticada por sessão).
 *
 * Contrato de payload:
 *  - GET    /api/categories        → lista as 9 pré-definidas + as personalizadas do usuário.
 *  - POST   /api/categories        → cria categoria personalizada do usuário; corpo: { name, icon?, color? }
 *  - PUT    /api/categories/{id}   → atualiza categoria personalizada do usuário.
 *  - DELETE /api/categories/{id}   → exclui (soft delete) categoria personalizada do usuário.
 *
 * As 9 categorias pré-definidas (user_id nulo, is_predefined=true) NÃO podem ser
 * editadas nem excluídas pelo usuário — as escritas são escopadas às categorias
 * do próprio usuário ($request->user()->categories()), então uma pré-definida
 * resulta em 404. A listagem é um conjunto pequeno e limitado (referência para
 * seletores), por isso não é paginada.
 */
class CategoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $userId = $request->user()->id;

        $categories = Category::query()
            ->where(function ($query) use ($userId) {
                $query->where('is_predefined', true)
                    ->orWhere('user_id', $userId);
            })
            ->orderByDesc('is_predefined')
            ->orderBy('name')
            ->get();

        return CategoryResource::collection($categories);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $request->user()->categories()->create([
            ...$request->validated(),
            'is_predefined' => false,
        ]);

        return CategoryResource::make($category)
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateCategoryRequest $request, int $id): CategoryResource
    {
        // Escopo: só categorias do próprio usuário (pré-definidas têm user_id nulo → 404).
        $category = $request->user()->categories()->findOrFail($id);

        $category->update($request->validated());

        return CategoryResource::make($category);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $category = $request->user()->categories()->findOrFail($id);

        $category->delete();            // soft delete

        return response()->json(['message' => 'Categoria excluída com sucesso.']);
    }
}
