// Componentes Alpine do FlowFin (registro de transação, categorias, histórico).
// Registrados em `alpine:init` para estarem disponíveis antes do Alpine.start().

import { api, ApiError } from './api.js';
import { centsToBRL, centsToBRLNumber, brlToCents, maskCurrency, formatDateBR, todayISO } from './format.js';
import { iconSvg } from './icons.js';

function toast(type, message) {
    window.dispatchEvent(new CustomEvent('toast', { detail: { type, message } }));
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

document.addEventListener('alpine:init', () => {
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
});
