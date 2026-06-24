<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EducationalContentResource;
use App\Models\EducationalContent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * API JSON dos mini-conteúdos educativos do sistema (não escopados por usuário).
 * Autenticada por sessão.
 *
 *  - GET /api/educational-contents?theme=&page= → lista paginada (20/página):
 *      data: [ { id, title, theme, body } ], com metadados de paginação.
 *    Filtro opcional por `theme`.
 */
class EducationalContentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'theme' => ['nullable', 'string', 'max:100'],
        ]);

        $contents = EducationalContent::query()
            ->when($validated['theme'] ?? null, fn ($query, $theme) => $query->where('theme', $theme))
            ->orderBy('id')
            ->paginate(20);

        return EducationalContentResource::collection($contents);
    }
}
