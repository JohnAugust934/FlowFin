<?php

namespace App\Http\Requests;

use App\Support\Money;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validação da criação de um orçamento por categoria (sempre do usuário autenticado).
 *
 * Regras de negócio:
 *  - `monthly_limit` em CENTAVOS (inteiro); string em R$ é aceita e convertida.
 *  - A categoria precisa ser pré-definida ou do próprio usuário.
 *  - Apenas um orçamento ativo por categoria por usuário.
 */
class StoreBudgetRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $limit = $this->input('monthly_limit');

        if (is_string($limit) && preg_match('/[,R$]/', $limit)) {
            $this->merge(['monthly_limit' => Money::toCents($limit)]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(function ($query) {
                    $query->whereNull('deleted_at')
                        ->where(function ($q) {
                            $q->where('is_predefined', true)
                                ->orWhere('user_id', $this->user()->id);
                        });
                }),
                // Um orçamento ativo por categoria por usuário.
                Rule::unique('budgets', 'category_id')
                    ->where(fn ($query) => $query->where('user_id', $this->user()->id)->whereNull('deleted_at')),
            ],
            'monthly_limit' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.unique' => 'Já existe um orçamento para esta categoria.',
        ];
    }
}
