<?php

namespace App\Http\Resources;

use App\Models\EducationalContent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Contrato de resposta de um mini-conteúdo educativo.
 *
 * @mixin EducationalContent
 */
class EducationalContentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'theme' => $this->theme,
            'body' => $this->body,
        ];
    }
}
