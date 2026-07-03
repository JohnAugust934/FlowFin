<?php

namespace App\Http\Requests;

use App\Support\Money;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validação do simulador de metas. Informe exatamente DOIS dos três campos:
 * `monthly_amount` (centavos), `target_amount` (centavos) e `months`; o terceiro é
 * calculado. Valores monetários aceitam R$ em string e são convertidos.
 */
class SimulateGoalRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        foreach (['monthly_amount', 'target_amount'] as $field) {
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
            'monthly_amount' => ['nullable', 'integer', 'min:1'],
            'target_amount' => ['nullable', 'integer', 'min:1'],
            'months' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                $informados = collect(['monthly_amount', 'target_amount', 'months'])
                    ->filter(fn ($field) => $this->filled($field))
                    ->count();

                if ($informados !== 2) {
                    $validator->errors()->add('months', 'Informe exatamente dois dos três valores: valor mensal, valor-alvo e número de meses.');
                }
            },
        ];
    }
}
