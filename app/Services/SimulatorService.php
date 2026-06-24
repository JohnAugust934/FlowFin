<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * Simulador de metas (Pilar 5) — cálculo determinístico, sem juros.
 *
 * Relaciona três grandezas e, dados DOIS, calcula o terceiro:
 *   - monthly_amount (X): valor guardado por mês, em centavos.
 *   - target_amount  (Y): valor-alvo a atingir, em centavos.
 *   - months         (Z): número de meses.
 *
 * Fórmulas EXATAS adotadas (poupança simples, sem rendimento):
 *   - Faltando os meses:   Z = ceil(Y / X)   — quantos meses guardando X/mês atingem Y.
 *   - Faltando o alvo:     Y = X * Z          — quanto se acumula guardando X/mês por Z meses.
 *   - Faltando o mensal:   X = ceil(Y / Z)    — quanto guardar por mês para atingir Y em Z meses.
 *
 * O arredondamento dos casos com divisão é para CIMA (ceil): garante que, ao final,
 * o alvo é de fato alcançado (nunca subestima o esforço necessário).
 */
class SimulatorService
{
    /**
     * @param  int|null  $monthlyAmount  centavos
     * @param  int|null  $targetAmount  centavos
     * @return array{monthly_amount: int, target_amount: int, months: int, computed: string}
     */
    public function simulate(?int $monthlyAmount, ?int $targetAmount, ?int $months): array
    {
        $faltando = array_keys(array_filter([
            'monthly_amount' => $monthlyAmount === null,
            'target_amount' => $targetAmount === null,
            'months' => $months === null,
        ]));

        if (count($faltando) !== 1) {
            throw new InvalidArgumentException('Informe exatamente dois dos três valores: monthly_amount, target_amount e months.');
        }

        $computed = $faltando[0];

        return match ($computed) {
            'months' => $this->result($monthlyAmount, $targetAmount, (int) ceil($targetAmount / $monthlyAmount), 'months'),
            'target_amount' => $this->result($monthlyAmount, $monthlyAmount * $months, $months, 'target_amount'),
            'monthly_amount' => $this->result((int) ceil($targetAmount / $months), $targetAmount, $months, 'monthly_amount'),
        };
    }

    /**
     * @return array{monthly_amount: int, target_amount: int, months: int, computed: string}
     */
    private function result(int $monthlyAmount, int $targetAmount, int $months, string $computed): array
    {
        return [
            'monthly_amount' => $monthlyAmount,
            'target_amount' => $targetAmount,
            'months' => $months,
            'computed' => $computed,
        ];
    }
}
