<?php

namespace App\Http\Requests;

use App\Support\Money;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validação da atualização de uma meta. Campos opcionais (atualização parcial);
 * valores monetários em CENTAVOS (inteiro), com aceitação de R$ em string.
 */
class UpdateGoalRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'target_amount' => ['sometimes', 'required', 'integer', 'min:1'],
            'saved_amount' => ['sometimes', 'required', 'integer', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'priority' => ['sometimes', 'required', 'in:baixa,media,alta'],
        ];
    }
}
