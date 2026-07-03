<?php

namespace App\Http\Resources;

use App\Models\Recurrence;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Contrato de resposta de uma recorrência (conta fixa).
 *
 * `amount` sempre em CENTAVOS (inteiro). Datas em "aaaa-mm-dd".
 *
 * @mixin Recurrence
 */
class RecurrenceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'type' => $this->type,                       // 'entrada' | 'saida'
            'amount' => $this->amount,                   // centavos (inteiro)
            'frequency' => $this->frequency,             // 'diaria' | 'semanal' | 'mensal' | 'anual'
            'category_id' => $this->category_id,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'next_due_date' => $this->next_due_date?->format('Y-m-d'),
            'is_active' => $this->is_active,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
