<?php

namespace App\Http\Resources;

use App\Models\Investment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Contrato de resposta de um investimento (registro simplificado, Pilar 5).
 *
 * `amount` sempre em CENTAVOS (inteiro).
 *
 * @mixin Investment
 */
class InvestmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'type' => $this->type,
            'amount' => (int) $this->amount, // centavos
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
