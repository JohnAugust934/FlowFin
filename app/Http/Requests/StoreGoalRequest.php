<?php

namespace App\Http\Requests;

use App\Support\Money;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validação da criação de uma meta (sempre do usuário autenticado).
 *
 * Valores monetários em CENTAVOS (inteiro); strings em R$ são aceitas e convertidas.
 * `description` é o propósito da meta.
 */
class StoreGoalRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        foreach (['target_amount', 'saved_amount'] as $field) {
            $value = $this->input($field);
            if (is_string($value) && preg_match('/[,R$]/', $value)) {
                $this->merge([$field => Money::toCents($value)]);
            }
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'target_amount' => ['required', 'integer', 'min:1'],
            'saved_amount' => ['nullable', 'integer', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'priority' => ['nullable', 'in:baixa,media,alta'],
        ];
    }
}
