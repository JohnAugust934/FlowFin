<?php

namespace App\Http\Requests;

use App\Support\Money;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validação do registro de um investimento (sempre do usuário autenticado).
 *
 * `amount` em CENTAVOS (inteiro); string em R$ é aceita e convertida. `type` é livre.
 */
class StoreInvestmentRequest extends FormRequest
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
            'description' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:100'],
            'amount' => ['required', 'integer', 'min:0'],
        ];
    }
}
