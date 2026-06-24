<?php

namespace App\Http\Requests;

use App\Support\Money;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validação da criação de uma conta fixa / recorrência (sempre do usuário autenticado).
 *
 * Contrato de `amount`: CENTAVOS (inteiro). Uma string formatada em R$ também é
 * aceita e convertida via App\Support\Money, como nas transações.
 */
class StoreRecurrenceRequest extends FormRequest
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
                // Categoria precisa existir e ser pré-definida ou do próprio usuário.
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
