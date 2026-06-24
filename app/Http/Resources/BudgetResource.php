<?php

namespace App\Http\Resources;

use App\Models\Budget;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Contrato de resposta de um orçamento por categoria.
 *
 * `monthly_limit` sempre em CENTAVOS (inteiro). O status semafórico calculado
 * (consumido/limite) é exposto pelo endpoint de status, não por este recurso.
 *
 * @mixin Budget
 */
class BudgetResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'monthly_limit' => $this->monthly_limit,     // centavos (inteiro)
            'category' => new CategoryResource($this->whenLoaded('category')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
