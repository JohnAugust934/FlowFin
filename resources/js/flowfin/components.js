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

    // ------------------------------------------------------------------
    // Mentalidade (Pilar 4): Score FlowFin, streak de registro, dicas
    // contextuais e mini-conteúdos educativos. Consome /api/score,
    // /api/streak, /api/tips e /api/educational-contents. Recarrega ao
    // ouvir `transaction-saved`.
    // ------------------------------------------------------------------
    Alpine.data('mentalidade', () => ({
        loading: true,
        error: null,
        month: null,
        score: null,
        streak: null,
        tips: [],

        // Conteúdos educativos (paginados + filtro por tema).
        contents: [],
        contentsMeta: {},
        contentsTheme: '',
        loadingContents: false,
        themesSeen: [],

        async init() {
            this.month = currentMonth();
            await this.load();
            window.addEventListener('transaction-saved', () => this.load());
        },

        async load() {
            this.loading = true;
            this.error = null;
            try {
                const [score, streak, tips] = await Promise.all([
                    api.getScore(this.month),
                    api.getStreak(),
                    api.getTips(this.month),
                ]);
                this.score = score;
                this.month = score.month || this.month;
                this.streak = streak;
                this.tips = tips;
            } catch (e) {
                this.error = e.message;
                toast('error', e.message);
            } finally {
                this.loading = false;
            }
            await this.loadContents(1);
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

        // ============ Score ============
        get scoreValue() {
            return this.score?.score ?? 0;
        },
        // Cor do anel/score por faixa (semáforo): <40 ruim, <70 atenção, >=70 bom.
        get scoreColor() {
            const s = this.scoreValue;
            if (s >= 70) return '#10B981'; // emerald
            if (s >= 40) return '#F59E0B'; // amber
            return '#EF4444';              // red
        },
        get scoreLabel() {
            const s = this.scoreValue;
            if (s >= 70) return 'Você está no caminho certo';
            if (s >= 40) return 'Dá pra melhorar';
            return 'Hora de cuidar das finanças';
        },
        // Circunferência do anel SVG (r=52) para desenhar o progresso.
        get scoreDash() {
            const c = 2 * Math.PI * 52;
            const filled = (this.scoreValue / 100) * c;
            return `${filled} ${c}`;
        },
        get scoreFactors() {
            const f = this.score?.factors ?? {};
            const meta = {
                consistency: { label: 'Consistência', hint: 'Registrar seus gastos com frequência' },
                budgets: { label: 'Orçamentos', hint: 'Manter os gastos dentro dos limites' },
                savings_goal: { label: 'Meta de economia', hint: 'Guardar o quanto planejou' },
            };
            return Object.entries(meta).map(([key, m]) => {
                const data = f[key] || {};
                const included = data.included !== false;
                const value = data.value;
                return {
                    key,
                    label: m.label,
                    hint: m.hint,
                    included,
                    hasValue: included && value !== null && value !== undefined,
                    value: value ?? 0,
                    valueLabel: (included && value !== null && value !== undefined) ? Math.round(value) + '/100' : '—',
                    weightLabel: Math.round((data.weight ?? 0)) + '%',
                    width: included && value !== null && value !== undefined ? Math.min(100, Math.max(0, value)) : 0,
                };
            });
        },

        // ============ Streak ============
        get streakCount() { return this.streak?.current_streak ?? 0; },
        get streakActive() { return !!this.streak?.active; },
        get streakLabel() {
            const n = this.streakCount;
            if (n <= 0) return 'Sem sequência ativa';
            return n === 1 ? '1 dia seguido' : `${n} dias seguidos`;
        },
        get lastActivityLabel() {
            const d = this.streak?.last_activity_date;
            return d ? formatDateBR(d) : null;
        },

        // ============ Dicas ============
        get tipRows() {
            const cls = {
                alerta: { badge: 'bg-danger-light text-danger-dark', dot: 'bg-danger', label: 'Atenção' },
                positivo: { badge: 'bg-success-light text-success-dark', dot: 'bg-success', label: 'Positivo' },
                educativo: { badge: 'bg-brand-50 text-brand-700 dark:bg-brand-500/20 dark:text-brand-200', dot: 'bg-brand-500', label: 'Dica' },
            };
            return (this.tips ?? []).map((t, i) => ({
                ...t,
                key: t.code || i,
                badgeClass: (cls[t.level] || cls.educativo).badge,
                dotClass: (cls[t.level] || cls.educativo).dot,
                levelLabel: (cls[t.level] || cls.educativo).label,
            }));
        },
        get hasTips() { return this.tipRows.length > 0; },

        // ============ Conteúdos educativos ============
        async loadContents(page = 1) {
            this.loadingContents = true;
            try {
                const res = await api.getEducationalContents({ theme: this.contentsTheme, page });
                this.contents = res.data || [];
                this.contentsMeta = res.meta || {};
                // Acumula temas vistos para alimentar o filtro (sem endpoint dedicado).
                const set = new Set(this.themesSeen);
                this.contents.forEach((c) => c.theme && set.add(c.theme));
                this.themesSeen = Array.from(set).sort();
            } catch (e) {
                toast('error', e.message);
            } finally {
                this.loadingContents = false;
            }
        },
        get hasContents() { return this.contents.length > 0; },
        get canPrevContents() { return (this.contentsMeta.current_page || 1) > 1; },
        get canNextContents() { return (this.contentsMeta.current_page || 1) < (this.contentsMeta.last_page || 1); },
        prevContents() { if (this.canPrevContents) this.loadContents(this.contentsMeta.current_page - 1); },
        nextContents() { if (this.canNextContents) this.loadContents(this.contentsMeta.current_page + 1); },
        filterByTheme(theme) {
            this.contentsTheme = theme;
            this.loadContents(1);
        },
    }));

    // ------------------------------------------------------------------
    // Direcionamento (Pilar 5): metas com propósito, simulador interativo,
    // prioridades e investimentos. Consome /api/goals (+ /simulate) e
    // /api/investments.
    // ------------------------------------------------------------------
    Alpine.data('direcionamento', () => ({
        loading: true,
        error: null,

        goals: [],
        goalsMeta: {},
        investments: [],
        investmentsMeta: {},
        totalInvested: 0,

        // Formulário de meta (criar/editar).
        goalForm: { open: false, id: null, name: '', description: '', target: '', saved: '', due_date: '', priority: 'media' },
        savingGoal: false,
        confirmingGoalId: null,
        goalErrors: {},

        // Formulário de investimento (criar/editar).
        investForm: { open: false, id: null, description: '', type: '', amount: '' },
        savingInvest: false,
        confirmingInvestId: null,
        investErrors: {},

        // Simulador interativo.
        sim: { monthly: '', target: '', months: '', result: null, message: '', computing: false },

        async init() {
            await this.load();
            window.addEventListener('transaction-saved', () => this.load());
        },

        async load() {
            this.loading = true;
            this.error = null;
            try {
                await Promise.all([this.loadGoals(1), this.loadInvestments(1)]);
            } catch (e) {
                this.error = e.message;
                toast('error', e.message);
            } finally {
                this.loading = false;
            }
        },

        // ============ Metas ============
        async loadGoals(page = 1) {
            const res = await api.getGoals(page);
            this.goals = res.data || [];
            this.goalsMeta = res.meta || {};
        },
        get goalRows() {
            return this.goals.map((g) => ({
                ...g,
                targetLabel: centsToBRL(g.target_amount),
                savedLabel: centsToBRL(g.saved_amount),
                remainingLabel: centsToBRL(g.remaining_amount),
                width: Math.min(100, Math.max(0, g.progress_pct ?? 0)),
                pctLabel: Math.round(g.progress_pct ?? 0) + '%',
                achieved: (g.progress_pct ?? 0) >= 100,
                dueDateLabel: g.due_date ? formatDateBR(g.due_date) : null,
                priorityLabel: this.priorityLabel(g.priority),
                priorityClass: this.priorityClass(g.priority),
            }));
        },
        get hasGoals() { return this.goals.length > 0; },
        get canPrevGoals() { return (this.goalsMeta.current_page || 1) > 1; },
        get canNextGoals() { return (this.goalsMeta.current_page || 1) < (this.goalsMeta.last_page || 1); },
        prevGoals() { if (this.canPrevGoals) this.loadGoals(this.goalsMeta.current_page - 1); },
        nextGoals() { if (this.canNextGoals) this.loadGoals(this.goalsMeta.current_page + 1); },

        // Resumo de prioridades (na página atual) para destaque visual.
        get priorityCounts() {
            const counts = { alta: 0, media: 0, baixa: 0 };
            this.goals.forEach((g) => { if (counts[g.priority] !== undefined) counts[g.priority]++; });
            return [
                { key: 'alta', label: 'Alta', count: counts.alta, class: this.priorityClass('alta') },
                { key: 'media', label: 'Média', count: counts.media, class: this.priorityClass('media') },
                { key: 'baixa', label: 'Baixa', count: counts.baixa, class: this.priorityClass('baixa') },
            ];
        },

        priorityLabel(p) {
            return { alta: 'Alta', media: 'Média', baixa: 'Baixa' }[p] || p;
        },
        priorityClass(p) {
            return {
                alta: 'bg-danger-light text-danger-dark',
                media: 'bg-warning-light text-warning-dark',
                baixa: 'bg-success-light text-success-dark',
            }[p] || 'bg-neutral-100 text-neutral-600';
        },

        startGoalCreate() {
            this.goalErrors = {};
            this.confirmingGoalId = null;
            this.goalForm = { open: true, id: null, name: '', description: '', target: '', saved: '', due_date: '', priority: 'media' };
        },
        startGoalEdit(g) {
            this.goalErrors = {};
            this.confirmingGoalId = null;
            this.goalForm = {
                open: true, id: g.id, name: g.name, description: g.description || '',
                target: centsToBRLNumber(g.target_amount), saved: centsToBRLNumber(g.saved_amount),
                due_date: g.due_date || '', priority: g.priority || 'media',
            };
        },
        cancelGoalForm() {
            this.goalForm = { open: false, id: null, name: '', description: '', target: '', saved: '', due_date: '', priority: 'media' };
            this.goalErrors = {};
        },
        onGoalTargetInput(e) { this.goalForm.target = maskCurrency(e.target.value); },
        onGoalSavedInput(e) { this.goalForm.saved = maskCurrency(e.target.value); },
        get canSaveGoal() {
            return this.goalForm.name.trim() !== '' && brlToCents(this.goalForm.target) > 0 && !this.savingGoal;
        },
        async saveGoal() {
            this.goalErrors = {};
            this.savingGoal = true;
            const payload = {
                name: this.goalForm.name,
                description: this.goalForm.description || null,
                target_amount: brlToCents(this.goalForm.target),
                saved_amount: brlToCents(this.goalForm.saved) || 0,
                due_date: this.goalForm.due_date || null,
                priority: this.goalForm.priority,
            };
            try {
                if (this.goalForm.id) {
                    await api.updateGoal(this.goalForm.id, payload);
                    toast('success', 'Meta atualizada ✓');
                } else {
                    await api.createGoal(payload);
                    toast('success', 'Meta criada ✓');
                }
                this.cancelGoalForm();
                await this.loadGoals(this.goalsMeta.current_page || 1);
            } catch (e) {
                if (e instanceof ApiError && e.status === 422) {
                    this.goalErrors = e.errors;
                    toast('error', 'Confira os campos destacados.');
                } else {
                    toast('error', e.message);
                }
            } finally {
                this.savingGoal = false;
            }
        },
        goalError(name) { return this.goalErrors[name]?.[0]; },
        confirmDeleteGoal(id) { this.confirmingGoalId = id; },
        cancelDeleteGoal() { this.confirmingGoalId = null; },
        async removeGoal(id) {
            try {
                await api.deleteGoal(id);
                toast('success', 'Meta excluída ✓');
                this.confirmingGoalId = null;
                // Se a página ficar vazia após excluir, volta uma página.
                const page = this.goals.length === 1 && this.canPrevGoals ? this.goalsMeta.current_page - 1 : (this.goalsMeta.current_page || 1);
                await this.loadGoals(page);
            } catch (e) {
                toast('error', e.message);
            }
        },

        // ============ Simulador ============
        onSimMonthlyInput(e) { this.sim.monthly = maskCurrency(e.target.value); this.runSim(); },
        onSimTargetInput(e) { this.sim.target = maskCurrency(e.target.value); this.runSim(); },
        onSimMonthsInput(e) { this.sim.months = e.target.value.replace(/\D/g, ''); this.runSim(); },
        get simFilled() {
            return [
                brlToCents(this.sim.monthly) > 0,
                brlToCents(this.sim.target) > 0,
                Number(this.sim.months) > 0,
            ].filter(Boolean).length;
        },
        async runSim() {
            this.sim.result = null;
            if (this.simFilled !== 2) {
                this.sim.message = 'Preencha exatamente dois campos para calcular o terceiro.';
                return;
            }
            const payload = {};
            if (brlToCents(this.sim.monthly) > 0) payload.monthly_amount = brlToCents(this.sim.monthly);
            if (brlToCents(this.sim.target) > 0) payload.target_amount = brlToCents(this.sim.target);
            if (Number(this.sim.months) > 0) payload.months = Number(this.sim.months);
            this.sim.computing = true;
            this.sim.message = '';
            try {
                this.sim.result = await api.simulateGoal(payload);
            } catch (e) {
                this.sim.message = e instanceof ApiError ? 'Não foi possível calcular com esses valores.' : e.message;
            } finally {
                this.sim.computing = false;
            }
        },
        get simPhrase() {
            const r = this.sim.result;
            if (!r) return '';
            return `Guardando ${centsToBRL(r.monthly_amount)} por mês, você atinge ${centsToBRL(r.target_amount)} em ${r.months} ${r.months === 1 ? 'mês' : 'meses'}.`;
        },
        useSimInGoal() {
            const r = this.sim.result;
            if (!r) return;
            this.startGoalCreate();
            this.goalForm.target = centsToBRLNumber(r.target_amount);
        },

        // ============ Investimentos ============
        async loadInvestments(page = 1) {
            const res = await api.getInvestments(page);
            this.investments = res.data || [];
            this.investmentsMeta = res.meta || {};
            this.totalInvested = res.total_invested ?? 0;
        },
        get investmentRows() {
            return this.investments.map((it) => ({
                ...it,
                amountLabel: centsToBRL(it.amount),
                typeLabel: it.type || 'Investimento',
            }));
        },
        get hasInvestments() { return this.investments.length > 0; },
        get totalInvestedLabel() { return centsToBRL(this.totalInvested); },
        get canPrevInvest() { return (this.investmentsMeta.current_page || 1) > 1; },
        get canNextInvest() { return (this.investmentsMeta.current_page || 1) < (this.investmentsMeta.last_page || 1); },
        prevInvest() { if (this.canPrevInvest) this.loadInvestments(this.investmentsMeta.current_page - 1); },
        nextInvest() { if (this.canNextInvest) this.loadInvestments(this.investmentsMeta.current_page + 1); },

        startInvestCreate() {
            this.investErrors = {};
            this.confirmingInvestId = null;
            this.investForm = { open: true, id: null, description: '', type: '', amount: '' };
        },
        startInvestEdit(it) {
            this.investErrors = {};
            this.confirmingInvestId = null;
            this.investForm = { open: true, id: it.id, description: it.description, type: it.type || '', amount: centsToBRLNumber(it.amount) };
        },
        cancelInvestForm() {
            this.investForm = { open: false, id: null, description: '', type: '', amount: '' };
            this.investErrors = {};
        },
        onInvestAmountInput(e) { this.investForm.amount = maskCurrency(e.target.value); },
        get canSaveInvest() {
            return this.investForm.description.trim() !== '' && brlToCents(this.investForm.amount) > 0 && !this.savingInvest;
        },
        async saveInvest() {
            this.investErrors = {};
            this.savingInvest = true;
            const payload = {
                description: this.investForm.description,
                type: this.investForm.type || null,
                amount: brlToCents(this.investForm.amount),
            };
            try {
                if (this.investForm.id) {
                    await api.updateInvestment(this.investForm.id, payload);
                    toast('success', 'Investimento atualizado ✓');
                } else {
                    await api.createInvestment(payload);
                    toast('success', 'Investimento registrado ✓');
                }
                this.cancelInvestForm();
                await this.loadInvestments(this.investmentsMeta.current_page || 1);
            } catch (e) {
                if (e instanceof ApiError && e.status === 422) {
                    this.investErrors = e.errors;
                    toast('error', 'Confira os campos destacados.');
                } else {
                    toast('error', e.message);
                }
            } finally {
                this.savingInvest = false;
            }
        },
        investError(name) { return this.investErrors[name]?.[0]; },
        confirmDeleteInvest(id) { this.confirmingInvestId = id; },
        cancelDeleteInvest() { this.confirmingInvestId = null; },
        async removeInvest(id) {
            try {
                await api.deleteInvestment(id);
                toast('success', 'Investimento excluído ✓');
                this.confirmingInvestId = null;
                const page = this.investments.length === 1 && this.canPrevInvest ? this.investmentsMeta.current_page - 1 : (this.investmentsMeta.current_page || 1);
                await this.loadInvestments(page);
            } catch (e) {
                toast('error', e.message);
            }
        },
    }));

    // ------------------------------------------------------------------
    // Perfil — Exportação de relatórios (CSV/PDF) e dos dados (LGPD).
    // Downloads não passam bem por fetch JSON: disparamos navegação direta
    // para a rota (a sessão já autentica) via um <a> temporário, que aciona
    // o download do navegador sem sair da página.
    // ------------------------------------------------------------------
    Alpine.data('exportData', () => ({
        month: currentMonth(),
        format: 'csv',
        downloading: false,
        downloadingFull: false,

        get maxMonth() {
            return currentMonth();
        },

        downloadMonthly() {
            if (!this.month) return;
            this.downloading = true;
            const url = `/api/export/monthly?month=${encodeURIComponent(this.month)}&format=${encodeURIComponent(this.format)}`;
            this._trigger(url);
            toast('success', 'Preparando seu relatório… o download começa em instantes.');
            // Sem evento confiável de "download concluído"; libera o botão após um breve intervalo.
            setTimeout(() => { this.downloading = false; }, 1500);
        },

        downloadFull() {
            this.downloadingFull = true;
            this._trigger('/api/export/full');
            toast('success', 'Preparando o arquivo com seus dados… o download começa em instantes.');
            setTimeout(() => { this.downloadingFull = false; }, 1500);
        },

        _trigger(url) {
            const a = document.createElement('a');
            a.href = url;
            a.rel = 'noopener';
            document.body.appendChild(a);
            a.click();
            a.remove();
        },
    }));

    // ------------------------------------------------------------------
    // Perfil — Exclusão definitiva da conta (LGPD). Reautentica por senha
    // via DELETE /api/account; 422 = senha incorreta (mensagem clara).
    // ------------------------------------------------------------------
    Alpine.data('accountDeletion', () => ({
        open: false,
        password: '',
        deleting: false,
        error: '',

        init() {
            this.$watch('open', (v) => {
                if (v) this.$nextTick(() => this.$refs.pwd?.focus());
            });
        },

        close() {
            if (this.deleting) return;
            this.open = false;
            this.password = '';
            this.error = '';
        },

        async confirm() {
            if (!this.password) return;
            this.deleting = true;
            this.error = '';
            try {
                await api.deleteAccount(this.password);
                toast('success', 'Conta excluída. Até logo.');
                window.location.href = '/';
            } catch (e) {
                if (e instanceof ApiError && e.status === 422) {
                    this.error = e.errors?.password?.[0] || 'Senha incorreta. Tente de novo.';
                } else {
                    this.error = e.message;
                }
                this.deleting = false;
            }
        },
    }));
});
