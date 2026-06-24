<?php

namespace App\Http\Requests;

use App\Support\Money;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validação da edição de um orçamento do usuário autenticado.
 *
 * A categoria do orçamento é fixa; apenas o limite mensal é editável.
 */
class UpdateBudgetRequest extends FormRequest
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
            'monthly_limit' => ['required', 'integer', 'min:0'],
        ];
    }
}
