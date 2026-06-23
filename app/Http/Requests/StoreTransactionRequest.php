<?php

namespace App\Http\Requests;

use App\Support\Money;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validação da criação de transação.
 *
 * Contrato de `amount`: a API recebe CENTAVOS (inteiro). Por conveniência,
 * uma string formatada em R$ ("R$ 1.234,56") também é aceita e convertida
 * para centavos via App\Support\Money. A formatação para exibição é da UI.
 */
class StoreTransactionRequest extends FormRequest
{
    /**
     * Converte `amount` formatado em R$ para centavos quando aplicável.
     */
    protected function prepareForValidation(): void
    {
        $amount = $this->input('amount');

        // Só converte quando vier claramente formatado em R$ (com separador/símbolo).
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
            'type' => ['required', Rule::in(['entrada', 'saida'])],
            // Valor em centavos (inteiro), não-negativo.
            'amount' => ['required', 'integer', 'min:0'],
            'category_id' => [
                'required',
                'integer',
                // A categoria precisa existir e ser pré-definida ou do próprio usuário.
                Rule::exists('categories', 'id')->where(function ($query) {
                    $query->whereNull('deleted_at')
                        ->where(function ($q) {
                            $q->where('is_predefined', true)
                                ->orWhere('user_id', $this->user()->id);
                        });
                }),
            ],
            'date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
            // Classificação aplicável apenas a saídas.
            'classification' => [
                'nullable',
                Rule::in(['necessidade', 'desejo']),
                Rule::prohibitedIf($this->input('type') === 'entrada'),
            ],
            'is_recurring' => ['nullable', 'boolean'],
        ];
    }
}
