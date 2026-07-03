<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Support\Money;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Converte a renda mensal estimada (entrada em R$) para centavos antes de validar.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('monthly_income')) {
            $this->merge([
                'monthly_income' => Money::toCents($this->input('monthly_income')),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            // Renda mensal estimada, já convertida para centavos (inteiro), opcional.
            'monthly_income' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
