<?php

namespace App\Http\Requests;

use App\Support\Money;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validação da definição da meta de economia mensal do usuário autenticado.
 *
 * `monthly_savings_goal` em CENTAVOS (inteiro); `null` limpa a meta. Uma string
 * formatada em R$ é aceita e convertida via App\Support\Money.
 */
class UpdateSavingsGoalRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $goal = $this->input('monthly_savings_goal');

        if (is_string($goal) && preg_match('/[,R$]/', $goal)) {
            $this->merge(['monthly_savings_goal' => Money::toCents($goal)]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'monthly_savings_goal' => ['present', 'nullable', 'integer', 'min:0'],
        ];
    }
}
