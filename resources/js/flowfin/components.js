// Componentes Alpine do FlowFin (registro de transação, categorias, histórico).
// Registrados em `alpine:init` para estarem disponíveis antes do Alpine.start().

import { api, ApiError } from './api.js';
import { centsToBRL, centsToBRLNumber, brlToCents, maskCurrency, formatDateBR, todayISO } from './format.js';
import { iconSvg } from './icons.js';

function toast(type, message) {
    window.dispatchEvent(new CustomEvent('toast', { detail: { type, message } }));
}

/** Mês atual no formato "aaaa-mm" (fuso local). */
function currentMonth() {
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
}

// Cache simples de categorias por sessão de página (evita refetch desnecessário).
let categoriesCache = null;
async function loadCategories(force = false) {
    if (categoriesCache && !force) return categoriesCache;
    categoriesCache = await api.getCategories();
    return categoriesCache;
}
function invalidateCategories() {
    categoriesCache = null;
}

/** Aplica o tema escolhido alternando a classe `dark` no <html>. */
function applyTheme(mode) {
    const dark = mode === 'dark' || (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
    document.documentElement.classList.toggle('dark', dark);
    window.dispatchEvent(new CustomEvent('theme-changed', { detail: { mode, dark } }));
}

document.addEventListener('alpine:init', () => {
    // ------------------------------------------------------------------
    // Controle de tema: claro / escuro / sistema. Persiste a escolha em
    // localStorage e aplica via classe `dark` no <html>. O script inline
    // no <head> já aplica antes da pintura para evitar flash de tema.
    // ------------------------------------------------------------------
    Alpine.data('themeControl', () => ({
        mode: 'system',
        _mql: null,

        init() {
            this.mode = localStorage.getItem('theme') || 'system';
            // Quando em "sistema", acompanha mudanças da preferência do SO.
            this._mql = window.matchMedia('(prefers-color-scheme: dark)');
            this._mql.addEventListener('change', () => {
                if (this.mode === 'system') applyTheme('system');
            });
        },

        set(mode) {
            this.mode = mode;
            localStorage.setItem('theme', mode);
            applyTheme(mode);
        },
    }));

    // ------------------------------------------------------------------
    // Formulário de transação (registro rápido ≤3 toques + edição).
    // Global no app shell; abre via evento `open-quick-add` (botão "+") ou
    // `edit-transaction` (com a transação no detail). Ao salvar, emite
    // `transaction-saved` para que listas se atualizem.
    // ------------------------------------------------------------------
    Alpine.data('transactionForm', () => ({
        open: false,
        saving: false,
        editingId: null,
        categories: [],
        loadingCategories: false,
        showMore: false,
        errors: {},
        form: {
            type: 'saida',
            amount: '',          // string mascarada em R$ (ex.: "12,34")
            category_id: null,
            classification: 'necessidade',
            date: todayISO(),
            description: '',
            is_recurring: false,
        },

        async openCreate() {
            this.resetForm();
            this.open = true;
            await this.ensureCategories();
            this.focusAmount();
        },

        async openEdit(tx) {
            this.resetForm();
            this.editingId = tx.id;
            this.form.type = tx.type;
            this.form.amount = centsToBRLNumber(tx.amount);
            this.form.category_id = tx.category_id;
            this.form.classification = tx.classification || 'necessidade';
            this.form.date = tx.date;
            this.form.description = tx.description || '';
            this.form.is_recurring = !!tx.is_recurring;
            this.showMore = true;
            this.open = true;
            await this.ensureCategories();
        },

        resetForm() {
            this.editingId = null;
            this.errors = {};
            this.showMore = false;
            this.form = {
                type: 'saida',
                amount: '',
                category_id: null,
                classification: 'necessidade',
                date: todayISO(),
                description: '',
                is_recurring: false,
            };
        },

        async ensureCategories() {
            if (this.categories.length) return;
            this.loadingCategories = true;
            try {
                this.categories = await loadCategories();
            } catch (e) {
                toast('error', e.message);
            } finally {
                this.loadingCategories = false;
            }
        },

        focusAmount() {
            this.$nextTick(() => this.$refs.amount?.focus());
        },

        onAmountInput(e) {
            this.form.amount = maskCurrency(e.target.value);
        },

        setType(type) {
            this.form.type = type;
        },

        selectCategory(id) {
            this.form.category_id = id;
        },

        iconFor(cat) {
            return iconSvg(cat.icon, 'w-6 h-6');
        },

        get amountCents() {
            return brlToCents(this.form.amount);
        },

        get canSave() {
            return this.amountCents > 0 && this.form.category_id && !this.saving;
        },

        close() {
            this.open = false;
        },

        async save() {
            this.errors = {};

            const payload = {
                type: this.form.type,
                amount: this.amountCents,
                category_id: this.form.category_id,
                date: this.form.date,
                description: this.form.description || null,
                is_recurring: this.form.is_recurring,
            };
            if (this.form.type === 'saida') {
                payload.classification = this.form.classification;
            }

            this.saving = true;
            try {
                // Ponto único de escrita (interceptável pela sincronização offline futura).
                const tx = await api.persistTransaction(payload, this.editingId);
                toast('success', this.editingId ? 'Transação atualizada ✓' : 'Transação registrada ✓');
                window.dispatchEvent(new CustomEvent('transaction-saved', { detail: tx }));
                this.close();
            } catch (e) {
                if (e instanceof ApiError && e.status === 422) {
                    this.errors = e.errors;
                    toast('error', 'Confira os campos destacados.');
                } else {
                    toast('error', e.message);
                }
            } finally {
                this.saving = false;
            }
        },

        fieldError(name) {
            return this.errors[name]?.[0];
        },
    }));

    // ------------------------------------------------------------------
    // Gestão de categorias personalizadas. Pré-definidas ficam protegidas.
    // ------------------------------------------------------------------
    Alpine.data('categoriesManager', () => ({
        categories: [],
        loading: true,
        saving: false,
        editing: false,
        confirmingId: null,
        errors: {},
        form: { id: null, name: '', icon: 'tag', color: '#2563EB' },

        async init() {
            await this.load();
        },

        async load() {
            this.loading = true;
            try {
                this.categories = await loadCategories(true);
            } catch (e) {
                toast('error', e.message);
            } finally {
                this.loading = false;
            }
        },

        get predefined() {
            return this.categories.filter((c) => c.is_predefined);
        },
        get custom() {
            return this.categories.filter((c) => !c.is_predefined);
        },

        iconFor(cat) {
            return iconSvg(cat.icon, 'w-6 h-6');
        },

        startCreate() {
            this.editing = false;
            this.errors = {};
            this.form = { id: null, name: '', icon: 'tag', color: '#2563EB' };
        },

        startEdit(cat) {
            if (cat.is_predefined) return; // protegidas
            this.editing = true;
            this.errors = {};
            this.form = { id: cat.id, name: cat.name, icon: cat.icon || 'tag', color: cat.color || '#2563EB' };
        },

        async save() {
            this.errors = {};
            this.saving = true;
            const payload = { name: this.form.name, icon: this.form.icon || null, color: this.form.color || null };
            try {
                if (this.editing && this.form.id) {
                    await api.updateCategory(this.form.id, payload);
                    toast('success', 'Categoria atualizada ✓');
                } else {
                    await api.createCategory(payload);
                    toast('success', 'Categoria criada ✓');
                }
                invalidateCategories();
                this.startCreate();
                await this.load();
            } catch (e) {
                if (e instanceof ApiError && e.status === 422) {
                    this.errors = e.errors;
                } else {
                    toast('error', e.message);
                }
            } finally {
                this.saving = false;
            }
        },

        confirmDelete(id) {
            this.confirmingId = id;
        },
        cancelDelete() {
            this.confirmingId = null;
        },
        async remove(cat) {
            if (cat.is_predefined) return;
            try {
                await api.deleteCategory(cat.id);
                toast('success', 'Categoria excluída ✓');
                invalidateCategories();
                this.confirmingId = null;
                await this.load();
            } catch (e) {
                toast('error', e.message);
            }
        },

        fieldError(name) {
            return this.errors[name]?.[0];
        },
    }));

    // ------------------------------------------------------------------
    // Histórico de transações: lista paginada, filtros, editar/excluir.
    // ------------------------------------------------------------------
    Alpine.data('transactionHistory', () => ({
        items: [],
        meta: {},
        categories: [],
        loading: true,
        deleting: false,
        confirmingId: null,
        filters: { date_from: '', date_to: '', category_id: '', type: '' },

        async init() {
            try {
                this.categories = await loadCategories();
            } catch (e) { /* segue sem categorias no filtro */ }
            await this.load(1);
            // Atualiza a lista quando uma transação é criada/editada em qualquer lugar.
            window.addEventListener('transaction-saved', () => this.load(this.meta.current_page || 1));
        },

        get hasActiveFilters() {
            return Object.values(this.filters).some((v) => v !== '' && v !== null);
        },

        async load(page = 1) {
            this.loading = true;
            try {
                const res = await api.getTransactions({ ...this.filters, page });
                // Os filtros são enviados como query params (prontos para o backend).
                // Enquanto a API não filtra no servidor, aplicamos um FALLBACK no
                // cliente sobre a página recebida (ver Task Log: requer ajuste de backend).
                this.items = this.applyClientFilters(res.data || []);
                this.meta = res.meta || {};
            } catch (e) {
                toast('error', e.message);
            } finally {
                this.loading = false;
            }
        },

        applyClientFilters(data) {
            return data.filter((tx) => {
                if (this.filters.type && tx.type !== this.filters.type) return false;
                if (this.filters.category_id && String(tx.category_id) !== String(this.filters.category_id)) return false;
                if (this.filters.date_from && tx.date < this.filters.date_from) return false;
                if (this.filters.date_to && tx.date > this.filters.date_to) return false;
                return true;
            });
        },

        applyFilters() {
            this.load(1);
        },
        clearFilters() {
            this.filters = { date_from: '', date_to: '', category_id: '', type: '' };
            this.load(1);
        },

        get canPrev() {
            return (this.meta.current_page || 1) > 1;
        },
        get canNext() {
            return (this.meta.current_page || 1) < (this.meta.last_page || 1);
        },
        prev() {
            if (this.canPrev) this.load(this.meta.current_page - 1);
        },
        next() {
            if (this.canNext) this.load(this.meta.current_page + 1);
        },

        editTransaction(tx) {
            window.dispatchEvent(new CustomEvent('edit-transaction', { detail: tx }));
        },

        confirmDelete(id) {
            this.confirmingId = id;
        },
        cancelDelete() {
            this.confirmingId = null;
        },
        async remove(id) {
            this.deleting = true;
            try {
                await api.deleteTransaction(id);
                toast('success', 'Transação excluída ✓');
                this.confirmingId = null;
                // Se a página ficar vazia após excluir, volta uma página.
                const page = this.items.length === 1 && this.canPrev ? this.meta.current_page - 1 : this.meta.current_page;
                await this.load(page || 1);
            } catch (e) {
                toast('error', e.message);
            } finally {
                this.deleting = false;
            }
        },

        // Helpers de exibição.
        money(cents) {
            return centsToBRL(cents);
        },
        dateBR(iso) {
            return formatDateBR(iso);
        },
        iconFor(tx) {
            return iconSvg(tx.category?.icon, 'w-5 h-5');
        },
        categoryName(tx) {
            return tx.category?.name || 'Sem categoria';
        },
    }));

    // ------------------------------------------------------------------
    // Dashboard do mês: cards entrou/saiu/sobrou, gráfico de rosca por
    // categoria e % necessidade vs. desejo. Consome /api/dashboard (valores
    // em centavos) e recarrega ao ouvir `transaction-saved`.
    // ------------------------------------------------------------------
    Alpine.data('dashboard', () => ({
        loading: true,
        error: null,
        month: null,        // "aaaa-mm" de referência
        data: null,         // payload do endpoint

        // Paleta de fallback p/ categorias sem cor definida (tons da marca/semáforo).
        palette: ['#2563EB', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#06B6D4', '#EC4899', '#84CC16', '#6B7280'],

        async init() {
            this.month = currentMonth();
            await this.load();
            // Recarrega quando uma transação é criada/editada em qualquer lugar do app.
            window.addEventListener('transaction-saved', () => this.load());
        },

        async load() {
            this.loading = true;
            this.error = null;
            try {
                this.data = await api.getDashboard(this.month);
                this.month = this.data.month; // normalizado pelo servidor
            } catch (e) {
                this.error = e.message;
                toast('error', e.message);
            } finally {
                this.loading = false;
            }
        },

        // --- Navegação de mês ---
        shiftMonth(delta) {
            const [y, m] = this.month.split('-').map(Number);
            const d = new Date(y, m - 1 + delta, 1);
            this.month = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
            this.load();
        },
        prevMonth() {
            this.shiftMonth(-1);
        },
        nextMonth() {
            if (this.canGoNext) this.shiftMonth(1);
        },
        get canGoNext() {
            return this.month < currentMonth();
        },
        get monthLabel() {
            const [y, m] = this.month.split('-').map(Number);
            const label = new Date(y, m - 1, 1).toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });
            return label.charAt(0).toUpperCase() + label.slice(1);
        },

        // --- Resumo ---
        get totals() {
            return this.data?.totals ?? { entrou: 0, saiu: 0, sobrou: 0 };
        },
        get byCategory() {
            return this.data?.by_category ?? [];
        },
        get needsVsWants() {
            return this.data?.needs_vs_wants ?? { necessidade: 0, desejo: 0, sem_classificacao: 0, necessidade_pct: 0, desejo_pct: 0 };
        },
        get hasCategoryData() {
            return this.byCategory.length > 0;
        },
        get hasClassification() {
            const n = this.needsVsWants;
            return (n.necessidade + n.desejo) > 0;
        },
        // Necessidade vs. desejo como linhas de ranking (mesma linguagem visual do
        // ranking de categorias): ícone+cor, valor em R$, % e barra de proporção.
        get needsVsWantsBreakdown() {
            const n = this.needsVsWants;
            return [
                {
                    key: 'necessidade',
                    name: 'Necessidade',
                    icon: 'home',
                    color: '#2563EB', // brand-600
                    total: n.necessidade,
                    moneyLabel: centsToBRL(n.necessidade),
                    pct: n.necessidade_pct,
                    pctLabel: n.necessidade_pct + '%',
                },
                {
                    key: 'desejo',
                    name: 'Desejo',
                    icon: 'sparkles',
                    color: '#10B981', // emerald-500
                    total: n.desejo,
                    moneyLabel: centsToBRL(n.desejo),
                    pct: n.desejo_pct,
                    pctLabel: n.desejo_pct + '%',
                },
            ];
        },
        // Soma das saídas do mês (centavos) e rótulo formatado.
        get totalSaidas() {
            return this.byCategory.reduce((sum, c) => sum + c.total, 0);
        },
        get totalSaidasLabel() {
            return centsToBRL(this.totalSaidas);
        },
        // Ranking de categorias com cor resolvida, valor em R$ e % do total de saídas.
        get categoryBreakdown() {
            const total = this.totalSaidas || 1;
            return this.byCategory.map((c, i) => ({
                id: c.category_id,
                name: c.name,
                icon: c.icon,
                color: this.colorFor(i, c.color),
                total: c.total,
                moneyLabel: centsToBRL(c.total),
                pct: (c.total / total) * 100,
                pctLabel: Math.round((c.total / total) * 100) + '%',
            }));
        },

        money(cents) {
            return centsToBRL(cents);
        },
        colorFor(index, color) {
            return color || this.palette[index % this.palette.length];
        },
        categoryIcon(icon) {
            return iconSvg(icon, 'w-5 h-5');
        },
    }));
});
