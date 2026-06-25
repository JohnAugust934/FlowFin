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

import { offlineQueue } from './offline-queue.js';

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

/**
 * Escrita de transação DIRETA NA REDE (sem interceptação offline).
 * Usada tanto pelo caminho online quanto pela fila ao sincronizar.
 */
async function persistTransactionNetwork(payload, id = null) {
    if (id) {
        return (await request('PUT', `/api/transactions/${id}`, payload)).data;
    }
    return (await request('POST', '/api/transactions', payload)).data;
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

    // --- Consciência (insights) ---
    /** Top 3 saídas, linha do tempo diária e comparativo mês a mês (valores em CENTAVOS). */
    async getInsights(month = null) {
        const qs = month ? `?month=${encodeURIComponent(month)}` : '';
        return await request('GET', '/api/insights' + qs);
    },
    /** Gastos "invisíveis" (recorrências de saída, impacto mensal somado). */
    async getInvisibleSpending() {
        return await request('GET', '/api/insights/invisible');
    },

    // --- Economia (orçamentos, meta, relatório) ---
    /** Status semafórico dos orçamentos do mês: [{id,category,monthly_limit,consumed,remaining,percentage,status}]. */
    async getBudgetsStatus(month = null) {
        const qs = month ? `?month=${encodeURIComponent(month)}` : '';
        return await request('GET', '/api/budgets/status' + qs);
    },
    async createBudget(payload) {
        return (await request('POST', '/api/budgets', payload)).data;
    },
    async updateBudget(id, payload) {
        return (await request('PUT', `/api/budgets/${id}`, payload)).data;
    },
    async deleteBudget(id) {
        return await request('DELETE', `/api/budgets/${id}`);
    },
    /** Meta de economia do mês: { month, goal, saved, progress_pct, achieved }. */
    async getSavingsGoal(month = null) {
        const qs = month ? `?month=${encodeURIComponent(month)}` : '';
        return await request('GET', '/api/savings-goal' + qs);
    },
    /** Define/limpa a meta (centavos; null limpa). Corpo: { monthly_savings_goal }. */
    async updateSavingsGoal(cents) {
        return await request('PUT', '/api/savings-goal', { monthly_savings_goal: cents });
    },
    /** Relatório "onde economizar": { month, total_potential_savings, count, suggestions:[...] }. */
    async getSavingsReport(month = null) {
        const qs = month ? `?month=${encodeURIComponent(month)}` : '';
        return await request('GET', '/api/savings-report' + qs);
    },

    // --- Mentalidade (Pilar 4): score, streak, dicas, conteúdos educativos) ---
    /** Score FlowFin do mês: { month, score, factors:{consistency,budgets,savings_goal} }. */
    async getScore(month = null) {
        const qs = month ? `?month=${encodeURIComponent(month)}` : '';
        return await request('GET', '/api/score' + qs);
    },
    /** Sequência de dias com registro: { current_streak, last_activity_date, active }. */
    async getStreak() {
        return await request('GET', '/api/streak');
    },
    /** Dicas contextuais (PT-BR): [ { code, level, title, message, theme } ]. */
    async getTips(month = null) {
        const qs = month ? `?month=${encodeURIComponent(month)}` : '';
        return await request('GET', '/api/tips' + qs);
    },
    /** Mini-conteúdos educativos paginados: { data, links, meta }. Filtro opcional por tema. */
    async getEducationalContents({ theme = '', page = 1 } = {}) {
        const query = new URLSearchParams();
        if (theme) query.append('theme', theme);
        if (page) query.append('page', page);
        const qs = query.toString();
        return await request('GET', '/api/educational-contents' + (qs ? `?${qs}` : ''));
    },

    // --- Direcionamento (Pilar 5): metas, simulador, investimentos ---
    /** Metas paginadas (ordenadas por prioridade/prazo): { data, links, meta }. */
    async getGoals(page = 1) {
        return await request('GET', '/api/goals' + (page ? `?page=${page}` : ''));
    },
    async createGoal(payload) {
        return (await request('POST', '/api/goals', payload)).data;
    },
    async updateGoal(id, payload) {
        return (await request('PUT', `/api/goals/${id}`, payload)).data;
    },
    async deleteGoal(id) {
        return await request('DELETE', `/api/goals/${id}`);
    },
    /** Simulador: envie EXATAMENTE dois de {monthly_amount, target_amount, months} (centavos/meses). */
    async simulateGoal(payload) {
        return await request('POST', '/api/goals/simulate', payload);
    },
    /** Investimentos paginados + total agregado: { data, links, meta, total_invested }. */
    async getInvestments(page = 1) {
        return await request('GET', '/api/investments' + (page ? `?page=${page}` : ''));
    },
    async createInvestment(payload) {
        return (await request('POST', '/api/investments', payload)).data;
    },
    async updateInvestment(id, payload) {
        return (await request('PUT', `/api/investments/${id}`, payload)).data;
    },
    async deleteInvestment(id) {
        return await request('DELETE', `/api/investments/${id}`);
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
        const op = id ? 'update' : 'create';

        // Sem conexão detectada: enfileira já (não tenta a rede à toa).
        if (!navigator.onLine) {
            return offlineQueue.enqueue({ op, id, payload });
        }

        try {
            return await persistTransactionNetwork(payload, id);
        } catch (e) {
            // Caiu a rede no meio do envio (status 0): preserva na fila offline.
            if (e instanceof ApiError && e.status === 0) {
                return offlineQueue.enqueue({ op, id, payload });
            }
            throw e; // 422 e demais erros seguem para a UI tratar normalmente.
        }
    },
};

// Liga a sincronização da fila ao ponto de escrita de rede (evita import circular).
offlineQueue.configure(persistTransactionNetwork);
