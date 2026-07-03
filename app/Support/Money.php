<?php

namespace App\Support;

/**
 * Helper de conversão monetária do FlowFin.
 *
 * Regra do projeto: dinheiro é sempre armazenado como inteiro em centavos.
 * A entrada chega em Real no formato brasileiro ("R$ 1.234,56") e é
 * convertida para centavos; a exibição formata os centavos de volta para R$.
 */
class Money
{
    /**
     * Converte uma entrada em Real (formato brasileiro) para centavos (inteiro).
     *
     * Aceita "R$ 1.234,56", "1.234,56", "1234,56" ou "1234.56".
     * Retorna null para entradas vazias/nulas.
     */
    public static function toCents(int|string|null $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        // Mantém apenas dígitos, separadores e sinal negativo.
        $value = preg_replace('/[^\d,.\-]/', '', $value) ?? '';

        if ($value === '' || $value === '-') {
            return null;
        }

        if (str_contains($value, ',')) {
            // Formato brasileiro: ponto é separador de milhar, vírgula é decimal.
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }

        // Sem vírgula: assume ponto como separador decimal (ou apenas dígitos).
        return (int) round(((float) $value) * 100);
    }

    /**
     * Formata centavos (inteiro) para o número em Real brasileiro, sem o prefixo.
     *
     * Ex.: 123456 → "1.234,56". Retorna string vazia para null.
     */
    public static function format(?int $cents): string
    {
        if ($cents === null) {
            return '';
        }

        return number_format($cents / 100, 2, ',', '.');
    }

    /**
     * Formata centavos para Real com prefixo: 123456 → "R$ 1.234,56".
     */
    public static function formatBRL(?int $cents): string
    {
        if ($cents === null) {
            return '';
        }

        return 'R$ '.self::format($cents);
    }
}
