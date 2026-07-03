<?php

namespace App\Http\Requests;

use App\Support\Money;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validação da atualização de um investimento. Campos opcionais (atualização parcial);
 * `amount` em CENTAVOS (inteiro), com aceitação de R$ em string.
 */
class UpdateInvestmentRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $value = $this->input('amount');
        if (is_string($value) && preg_match('/[,R$]/', $value)) {
            $this->merge(['amount' => Money::toCents($value)]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'description' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:100'],
            'amount' => ['sometimes', 'required', 'integer', 'min:0'],
        ];
    }
}
