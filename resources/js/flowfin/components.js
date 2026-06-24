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

    // ------------------------------------------------------------------
    // Consciência (Pilar 2): top 3 maiores saídas, linha do tempo diária e
    // comparativo mês a mês. Consome /api/insights (valores em centavos) e
    // recarrega ao ouvir `transaction-saved`.
    // ------------------------------------------------------------------
    Alpine.data('insights', () => ({
        loading: true,
        error: null,
        month: null,
        data: null,

        async init() {
            this.month = currentMonth();
            await this.load();
            window.addEventListener('transaction-saved', () => this.load());
        },

        async load() {
            this.loading = true;
            this.error = null;
            try {
                this.data = await api.getInsights(this.month);
                this.month = this.data.month;
            } catch (e) {
                this.error = e.message;
                toast('error', e.message);
            } finally {
                this.loading = false;
            }
        },

        // --- Navegação de mês (mesmo padrão do dashboard) ---
        shiftMonth(delta) {
            const [y, m] = this.month.split('-').map(Number);
            const d = new Date(y, m - 1 + delta, 1);
            this.month = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
            this.load();
        },
        prevMonth() { this.shiftMonth(-1); },
        nextMonth() { if (this.canGoNext) this.shiftMonth(1); },
        get canGoNext() { return this.month < currentMonth(); },
        get monthLabel() {
            const [y, m] = this.month.split('-').map(Number);
            const label = new Date(y, m - 1, 1).toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });
            return label.charAt(0).toUpperCase() + label.slice(1);
        },

        // --- Top 3 maiores saídas ---
        get topExpenses() {
            return (this.data?.top_expenses ?? []).map((t, i) => ({
                ...t,
                rank: i + 1,
                moneyLabel: centsToBRL(t.amount),
                dateLabel: formatDateBR(t.date),
                color: t.category?.color || '#6B7280',
                icon: t.category?.icon || 'tag',
                categoryName: t.category?.name || 'Sem categoria',
            }));
        },
        get hasTopExpenses() {
            return this.topExpenses.length > 0;
        },

        // --- Linha do tempo diária ---
        get timeline() {
            const days = this.data?.daily_timeline ?? [];
            const max = days.reduce((m, d) => Math.max(m, d.total), 0) || 1;
            return days.map((d) => ({
                date: d.date,
                day: Number(String(d.date).slice(8, 10)),
                total: d.total,
                moneyLabel: centsToBRL(d.total),
                pct: Math.round((d.total / max) * 100),
                hasValue: d.total > 0,
            }));
        },
        get hasTimeline() {
            return this.timeline.some((d) => d.hasValue);
        },

        // --- Comparativo mês a mês ---
        get comparison() {
            return this.data?.month_comparison ?? null;
        },
        get comparisonRows() {
            const c = this.comparison;
            if (!c) return [];
            return [
                { key: 'entrou', label: 'Entrou', current: c.current.entrou, previous: c.previous.entrou, pct: c.variation.entrou_pct, goodWhenUp: true },
                { key: 'saiu', label: 'Saiu', current: c.current.saiu, previous: c.previous.saiu, pct: c.variation.saiu_pct, goodWhenUp: false },
                { key: 'sobrou', label: 'Sobrou', current: c.current.sobrou, previous: c.previous.sobrou, pct: c.variation.sobrou_pct, goodWhenUp: true },
            ].map((r) => ({
                ...r,
                currentLabel: centsToBRL(r.current),
                previousLabel: centsToBRL(r.previous),
            }));
        },
        get previousMonthLabel() {
            const m = this.comparison?.previous?.month;
            if (!m) return '';
            const [y, mm] = m.split('-').map(Number);
            const label = new Date(y, mm - 1, 1).toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });
            return label.charAt(0).toUpperCase() + label.slice(1);
        },

        // Rótulo da variação: "—" quando não há base de comparação.
        variationLabel(pct) {
            if (pct === null || pct === undefined) return '—';
            return (pct > 0 ? '+' : '') + pct + '%';
        },
        // Cor semântica: para "saiu", subir é ruim; para entrou/sobrou, subir é bom.
        variationClass(pct, goodWhenUp) {
            if (pct === null || pct === undefined || pct === 0) {
                return 'text-neutral-400 dark:text-neutral-500';
            }
            const good = (pct > 0) === goodWhenUp;
            return good ? 'text-emerald-600 dark:text-emerald-400' : 'text-danger';
        },
        variationArrow(pct) {
            if (pct === null || pct === undefined || pct === 0) return '→';
            return pct > 0 ? '↑' : '↓';
        },

        money(cents) { return centsToBRL(cents); },
        dateBR(iso) { return formatDateBR(iso); },
        icon(name) { return iconSvg(name, 'w-5 h-5'); },
    }));

    // ------------------------------------------------------------------
    // Economia (Pilar 3): orçamentos semafóricos, meta de economia mensal,
    // gastos invisíveis e relatório "onde economizar". Consome /api/budgets,
    // /api/savings-goal, /api/insights/invisible e /api/savings-report.
    // ------------------------------------------------------------------
    Alpine.data('economia', () => ({
        loading: true,
        error: null,
        month: null,

        budgets: [],
        goal: null,
        invisible: { count: 0, total_monthly_impact: 0, items: [] },
        report: { total_potential_savings: 0, count: 0, suggestions: [] },
        categories: [],

        // Formulário de orçamento (criar/editar limite por categoria).
        budgetForm: { open: false, id: null, category_id: '', limit: '' },
        savingBudget: false,
        confirmingBudgetId: null,
        budgetErrors: {},

        // Formulário da meta de economia.
        goalForm: { open: false, amount: '' },
        savingGoal: false,

        async init() {
            this.month = currentMonth();
            await this.load();
            window.addEventListener('transaction-saved', () => this.load());
        },

        async load() {
            this.loading = true;
            this.error = null;
            try {
                const [budgets, goal, invisible, report, categories] = await Promise.all([
                    api.getBudgetsStatus(this.month),
                    api.getSavingsGoal(this.month),
                    api.getInvisibleSpending(),
                    api.getSavingsReport(this.month),
                    loadCategories(),
                ]);
                this.budgets = budgets;
                this.goal = goal;
                this.invisible = invisible;
                this.report = report;
                this.categories = categories;
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
        prevMonth() { this.shiftMonth(-1); },
        nextMonth() { if (this.canGoNext) this.shiftMonth(1); },
        get canGoNext() { return this.month < currentMonth(); },
        get monthLabel() {
            const [y, m] = this.month.split('-').map(Number);
            const label = new Date(y, m - 1, 1).toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });
            return label.charAt(0).toUpperCase() + label.slice(1);
        },

        // ============ Orçamentos ============
        get budgetRows() {
            return this.budgets.map((b) => ({
                ...b,
                color: b.category?.color || '#6B7280',
                icon: b.category?.icon || 'tag',
                categoryName: b.category?.name || 'Sem categoria',
                limitLabel: centsToBRL(b.monthly_limit),
                consumedLabel: centsToBRL(b.consumed),
                remainingLabel: centsToBRL(Math.abs(b.remaining)),
                overspent: b.remaining < 0,
                width: Math.min(100, b.percentage),
                pctLabel: Math.round(b.percentage) + '%',
                barClass: this.statusBar(b.status),
                badgeClass: this.statusBadge(b.status),
                statusLabel: this.statusLabel(b.status),
            }));
        },
        get hasBudgets() {
            return this.budgets.length > 0;
        },
        // Categorias ainda sem orçamento (para o seletor de novo orçamento).
        get availableCategories() {
            const used = new Set(this.budgets.map((b) => b.category?.id));
            return this.categories.filter((c) => !used.has(c.id));
        },

        statusBar(status) {
            return { ok: 'bg-gradient-emerald', alerta: 'bg-warning', estourado: 'bg-danger' }[status] || 'bg-gradient-emerald';
        },
        statusBadge(status) {
            return {
                ok: 'bg-success-light text-success-dark',
                alerta: 'bg-warning-light text-warning-dark',
                estourado: 'bg-danger-light text-danger-dark',
            }[status] || 'bg-neutral-100 text-neutral-600';
        },
        statusDot(status) {
            return { ok: 'bg-success', alerta: 'bg-warning', estourado: 'bg-danger' }[status] || 'bg-neutral-400';
        },
        statusLabel(status) {
            return { ok: 'No controle', alerta: 'Atenção', estourado: 'Estourou' }[status] || status;
        },

        startBudgetCreate() {
            this.budgetErrors = {};
            this.budgetForm = { open: true, id: null, category_id: '', limit: '' };
        },
        startBudgetEdit(b) {
            this.budgetErrors = {};
            this.confirmingBudgetId = null;
            this.budgetForm = { open: true, id: b.id, category_id: b.category?.id ?? '', limit: centsToBRLNumber(b.monthly_limit) };
        },
        cancelBudget() {
            this.budgetForm = { open: false, id: null, category_id: '', limit: '' };
            this.budgetErrors = {};
        },
        onBudgetLimitInput(e) {
            this.budgetForm.limit = maskCurrency(e.target.value);
        },
        get canSaveBudget() {
            const cents = brlToCents(this.budgetForm.limit);
            return cents > 0 && (this.budgetForm.id || this.budgetForm.category_id) && !this.savingBudget;
        },
        async saveBudget() {
            this.budgetErrors = {};
            this.savingBudget = true;
            const cents = brlToCents(this.budgetForm.limit);
            try {
                if (this.budgetForm.id) {
                    await api.updateBudget(this.budgetForm.id, { monthly_limit: cents });
                    toast('success', 'Orçamento atualizado ✓');
                } else {
                    await api.createBudget({ category_id: this.budgetForm.category_id, monthly_limit: cents });
                    toast('success', 'Orçamento criado ✓');
                }
                this.cancelBudget();
                await this.load();
            } catch (e) {
                if (e instanceof ApiError && e.status === 422) {
                    this.budgetErrors = e.errors;
                    toast('error', 'Confira os campos destacados.');
                } else {
                    toast('error', e.message);
                }
            } finally {
                this.savingBudget = false;
            }
        },
        budgetError(name) {
            return this.budgetErrors[name]?.[0];
        },
        confirmDeleteBudget(id) { this.confirmingBudgetId = id; },
        cancelDeleteBudget() { this.confirmingBudgetId = null; },
        async removeBudget(id) {
            try {
                await api.deleteBudget(id);
                toast('success', 'Orçamento excluído ✓');
                this.confirmingBudgetId = null;
                await this.load();
            } catch (e) {
                toast('error', e.message);
            }
        },

        // ============ Meta de economia ============
        get hasGoal() {
            return this.goal && this.goal.goal !== null && this.goal.goal > 0;
        },
        get goalView() {
            const g = this.goal || {};
            const saved = Math.max(0, g.saved ?? 0);
            return {
                goalLabel: centsToBRL(g.goal ?? 0),
                savedLabel: centsToBRL(g.saved ?? 0),
                savedPositive: (g.saved ?? 0) >= 0,
                pct: Math.round(g.progress_pct ?? 0),
                width: Math.min(100, Math.max(0, g.progress_pct ?? 0)),
                achieved: !!g.achieved,
                remaining: Math.max(0, (g.goal ?? 0) - saved),
                remainingLabel: centsToBRL(Math.max(0, (g.goal ?? 0) - (g.saved ?? 0))),
            };
        },
        startGoalEdit() {
            this.goalForm = { open: true, amount: this.hasGoal ? centsToBRLNumber(this.goal.goal) : '' };
        },
        cancelGoal() {
            this.goalForm = { open: false, amount: '' };
        },
        onGoalInput(e) {
            this.goalForm.amount = maskCurrency(e.target.value);
        },
        async saveGoal() {
            this.savingGoal = true;
            try {
                await api.updateSavingsGoal(brlToCents(this.goalForm.amount));
                toast('success', 'Meta atualizada ✓');
                this.cancelGoal();
                await this.load();
            } catch (e) {
                toast('error', e.message);
            } finally {
                this.savingGoal = false;
            }
        },
        async clearGoal() {
            this.savingGoal = true;
            try {
                await api.updateSavingsGoal(null);
                toast('success', 'Meta removida ✓');
                this.cancelGoal();
                await this.load();
            } catch (e) {
                toast('error', e.message);
            } finally {
                this.savingGoal = false;
            }
        },

        // ============ Gastos invisíveis ============
        get invisibleItems() {
            return (this.invisible?.items ?? []).map((it) => ({
                ...it,
                color: it.category?.color || '#6B7280',
                icon: it.category?.icon || 'tag',
                categoryName: it.category?.name || 'Sem categoria',
                impactLabel: centsToBRL(it.monthly_impact),
                frequencyLabel: this.frequencyLabel(it.frequency),
            }));
        },
        get hasInvisible() {
            return (this.invisible?.items?.length ?? 0) > 0;
        },
        get invisibleTotalLabel() {
            return centsToBRL(this.invisible?.total_monthly_impact ?? 0);
        },
        frequencyLabel(freq) {
            return { diaria: 'Por dia', semanal: 'Por semana', mensal: 'Por mês', anual: 'Por ano' }[freq] || freq;
        },

        // ============ Onde economizar ============
        get suggestions() {
            return (this.report?.suggestions ?? []).map((s) => ({
                ...s,
                color: s.category?.color || '#10B981',
                icon: s.category?.icon || 'sparkles',
                savingsLabel: centsToBRL(s.estimated_savings),
            }));
        },
        get hasSuggestions() {
            return this.suggestions.length > 0;
        },
        get potentialSavingsLabel() {
            return centsToBRL(this.report?.total_potential_savings ?? 0);
        },

        money(cents) { return centsToBRL(cents); },
        icon(name) { return iconSvg(name, 'w-5 h-5'); },
    }));
});
