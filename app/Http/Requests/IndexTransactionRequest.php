<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validação dos filtros (query params) da listagem de transações.
 *
 * Todos os filtros são opcionais. Os nomes dos params seguem o contrato já
 * usado pela UI de histórico: `date_from`, `date_to`, `category_id`, `type`.
 */
class IndexTransactionRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date'],
            // Intervalo inclusivo: o fim não pode ser anterior ao início.
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'category_id' => [
                'nullable',
                'integer',
                // A categoria precisa existir e ser pré-definida ou do próprio usuário
                // (não vaza categorias de outro usuário).
                Rule::exists('categories', 'id')->where(function ($query) {
                    $query->whereNull('deleted_at')
                        ->where(function ($q) {
                            $q->where('is_predefined', true)
                                ->orWhere('user_id', $this->user()->id);
                        });
                }),
            ],
            'type' => ['nullable', Rule::in(['entrada', 'saida'])],
        ];
    }
}
