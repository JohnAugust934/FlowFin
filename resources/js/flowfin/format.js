// Helpers de formatação do FlowFin (lado cliente).
// Dinheiro trafega em CENTAVOS (inteiro); aqui convertemos/formatamos para R$
// no formato brasileiro (ponto de milhar, vírgula decimal) e datas em dd/mm/aaaa.

/** Centavos (int) → número em Real BR sem prefixo. Ex.: 123456 → "1.234,56". */
export function centsToBRLNumber(cents) {
    const value = Number(cents || 0) / 100;
    return value.toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

/** Centavos (int) → Real BR com prefixo. Ex.: 123456 → "R$ 1.234,56". */
export function centsToBRL(cents) {
    return 'R$ ' + centsToBRLNumber(cents);
}

/**
 * String em R$ (formato BR) → centavos (int).
 * Aceita "R$ 1.234,56", "1.234,56", "1234,56", "1234.56" ou só dígitos.
 */
export function brlToCents(input) {
    if (input === null || input === undefined) return 0;
    if (typeof input === 'number') return Math.round(input * 100);

    let value = String(input).trim();
    if (value === '') return 0;

    // Mantém dígitos, separadores e sinal.
    value = value.replace(/[^\d,.-]/g, '');
    if (value === '' || value === '-') return 0;

    if (value.includes(',')) {
        // BR: ponto = milhar, vírgula = decimal.
        value = value.replace(/\./g, '').replace(',', '.');
    }

    return Math.round(parseFloat(value) * 100);
}

/**
 * Máscara progressiva para campo de valor: o usuário digita números e a parte
 * decimal preenche da direita para a esquerda (ex.: "1234" → "12,34").
 * Retorna a string formatada (sem prefixo R$).
 */
export function maskCurrency(raw) {
    const digits = String(raw).replace(/\D/g, '');
    if (digits === '') return '';
    const cents = parseInt(digits, 10);
    return centsToBRLNumber(cents);
}

/** ISO "aaaa-mm-dd" → "dd/mm/aaaa". */
export function formatDateBR(iso) {
    if (!iso) return '';
    const [y, m, d] = String(iso).slice(0, 10).split('-');
    if (!y || !m || !d) return iso;
    return `${d}/${m}/${y}`;
}

/** Data de hoje em ISO "aaaa-mm-dd" (fuso local). */
export function todayISO() {
    const now = new Date();
    const tzOffset = now.getTimezoneOffset() * 60000;
    return new Date(now - tzOffset).toISOString().slice(0, 10);
}
