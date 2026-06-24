<?php

namespace App\Http\Requests;

use App\Support\Money;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validação da edição de uma conta fixa / recorrência do usuário autenticado.
 */
class UpdateRecurrenceRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $amount = $this->input('amount');

        if (is_string($amount) && preg_match('/[,R$]/', $amount)) {
            $this->merge(['amount' => Money::toCents($amount)]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['entrada', 'saida'])],
            'amount' => ['required', 'integer', 'min:0'],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(function ($query) {
                    $query->whereNull('deleted_at')
                        ->where(function ($q) {
                            $q->where('is_predefined', true)
                                ->orWhere('user_id', $this->user()->id);
                        });
                }),
            ],
            'frequency' => ['required', Rule::in(['diaria', 'semanal', 'mensal', 'anual'])],
            'start_date' => ['required', 'date'],
            'next_due_date' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
