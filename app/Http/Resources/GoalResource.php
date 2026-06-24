<?php

namespace App\Http\Resources;

use App\Models\Goal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Contrato de resposta de uma meta com propósito (Pilar 5).
 *
 * Valores em CENTAVOS inteiros. `description` carrega o "propósito" (o porquê da meta).
 * `progress_pct` é o percentual do alvo já acumulado (0–100, capado).
 *
 * @mixin Goal
 */
class GoalResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $target = (int) $this->target_amount;
        $saved = (int) $this->saved_amount;
        $progress = $target > 0 ? min(100.0, round($saved * 100 / $target, 1)) : 0.0;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description, // propósito da meta
            'target_amount' => $target,         // centavos
            'saved_amount' => $saved,           // centavos
            'progress_pct' => $progress,
            'remaining_amount' => max(0, $target - $saved),
            'due_date' => $this->due_date?->format('Y-m-d'),
            'priority' => $this->priority,      // baixa | media | alta
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
