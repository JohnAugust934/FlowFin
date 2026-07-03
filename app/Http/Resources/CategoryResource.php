<?php

namespace App\Http\Resources;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Contrato de resposta de uma categoria.
 *
 * @mixin Category
 */
class CategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'icon' => $this->icon,
            'color' => $this->color,
            'is_predefined' => $this->is_predefined,
            // Categorias pré-definidas não têm dono (user_id nulo).
            'user_id' => $this->user_id,
        ];
    }
}
