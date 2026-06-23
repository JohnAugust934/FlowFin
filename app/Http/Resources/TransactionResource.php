<?php

namespace App\Http\Resources;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Contrato de resposta de uma transação (consumido pela UI e pela camada offline).
 *
 * IMPORTANTE: `amount` é sempre devolvido em CENTAVOS (inteiro). A formatação
 * para R$ é responsabilidade da interface.
 *
 * @mixin Transaction
 */
class TransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,                       // 'entrada' | 'saida'
            'amount' => $this->amount,                   // centavos (inteiro)
            'category_id' => $this->category_id,
            'date' => $this->date?->format('Y-m-d'),     // 'aaaa-mm-dd'
            'description' => $this->description,
            'classification' => $this->classification,   // 'necessidade' | 'desejo' | null
            'is_recurring' => $this->is_recurring,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
