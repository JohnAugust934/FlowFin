// Cliente HTTP centralizado da API JSON do FlowFin.
//
// Toda comunicação com a API passa por aqui. Em especial, AS ESCRITAS DE
// TRANSAÇÃO passam por um único ponto — `persistTransaction()` — que é o
// ponto de interceptação previsto para a futura sincronização offline.
// (Uma Task posterior poderá enfileirar/repetir a escrita sem mexer na UI.)
//
// Regras da API (sessão/Breeze):
//  - Sempre enviar `Accept: application/json` (senão auth/validação viram redirect).
//  - Escritas (POST/PUT/DELETE) enviam o token CSRF (meta `csrf-token`).
//  - `amount` trafega em CENTAVOS (inteiro).

/** Erro de API com status e (quando 422) os erros por campo. */
export class ApiError extends Error {
    constructor(message, status, errors = {}) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.errors = errors; // { campo: [mensagens] }
    }
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

async function request(method, url, body = null) {
    const headers = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };

    const options = { method, headers, credentials: 'same-origin' };

    if (body !== null) {
        headers['Content-Type'] = 'application/json';
        headers['X-CSRF-TOKEN'] = csrfToken();
        options.body = JSON.stringify(body);
    } else if (method !== 'GET') {
        headers['X-CSRF-TOKEN'] = csrfToken();
    }

    let response;
    try {
        response = await fetch(url, options);
    } catch (e) {
        // Falha de rede (offline, DNS, etc.).
        throw new ApiError('Sem conexão. Verifique sua internet e tente de novo.', 0);
    }

    // 204 / corpo vazio.
    const text = await response.text();
    const data = text ? JSON.parse(text) : null;

    if (!response.ok) {
        if (response.status === 422) {
            throw new ApiError(data?.message || 'Dados inválidos.', 422, data?.errors || {});
        }
        if (response.status === 401 || response.status === 419) {
            throw new ApiError('Sua sessão expirou. Entre novamente.', response.status);
        }
        if (response.status === 404) {
            throw new ApiError('Registro não encontrado.', 404);
        }
        throw new ApiError(data?.message || 'Algo deu errado. Tente de novo.', response.status);
    }

    return data;
}

export const api = {
    // --- Dashboard ---
    /**
     * Agregados do mês (entrou/saiu/sobrou, por categoria, % necessidade/desejo).
     * Todos os valores monetários vêm em CENTAVOS (inteiro).
     * @param {string|null} month  "aaaa-mm"; quando omitido, usa o mês atual.
     */
    async getDashboard(month = null) {
        const qs = month ? `?month=${encodeURIComponent(month)}` : '';
        return await request('GET', '/api/dashboard' + qs);
    },

    // --- Categorias ---
    async getCategories() {
        return (await request('GET', '/api/categories')).data;
    },
    async createCategory(payload) {
        return (await request('POST', '/api/categories', payload)).data;
    },
    async updateCategory(id, payload) {
        return (await request('PUT', `/api/categories/${id}`, payload)).data;
    },
    async deleteCategory(id) {
        return await request('DELETE', `/api/categories/${id}`);
    },

    // --- Transações (leitura) ---
    async getTransactions(params = {}) {
        const query = new URLSearchParams();
        Object.entries(params).forEach(([k, v]) => {
            if (v !== null && v !== undefined && v !== '') query.append(k, v);
        });
        const qs = query.toString();
        return await request('GET', '/api/transactions' + (qs ? `?${qs}` : ''));
    },
    async getTransaction(id) {
        return (await request('GET', `/api/transactions/${id}`)).data;
    },
    async deleteTransaction(id) {
        return await request('DELETE', `/api/transactions/${id}`);
    },

    /**
     * PONTO ÚNICO DE ESCRITA DE TRANSAÇÃO (criar/editar).
     * Centralizado de propósito: a sincronização offline futura interceptará
     * exatamente esta função. A UI nunca chama POST/PUT de transação direto.
     *
     * @param {object} payload  { type, amount(centavos), category_id, date, description?, classification?, is_recurring? }
     * @param {number|null} id  quando informado, atualiza (PUT); senão cria (POST).
     */
    async persistTransaction(payload, id = null) {
        if (id) {
            return (await request('PUT', `/api/transactions/${id}`, payload)).data;
        }
        return (await request('POST', '/api/transactions', payload)).data;
    },
};
