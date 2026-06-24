{{-- Histórico de transações: lista paginada, filtros e ações de editar/excluir. --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-neutral-800 dark:text-neutral-100">Transações</h1>
                <p class="text-sm text-neutral-500 dark:text-neutral-400">Suas entradas e saídas</p>
            </div>
            <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-quick-add'))" class="btn-primary hidden sm:inline-flex">
                + Nova
            </button>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-5" x-data="transactionHistory">

        {{-- Filtros --}}
        <x-card>
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">De</label>
                    <input type="date" x-model="filters.date_from"
                        class="block w-full border-neutral-300 rounded-lg focus:border-brand-500 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Até</label>
                    <input type="date" x-model="filters.date_to"
                        class="block w-full border-neutral-300 rounded-lg focus:border-brand-500 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Categoria</label>
                    <select x-model="filters.category_id"
                        class="block w-full border-neutral-300 rounded-lg focus:border-brand-500 focus:ring-brand-500">
                        <option value="">Todas</option>
                        <template x-for="cat in categories" :key="cat.id">
                            <option :value="cat.id" x-text="cat.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Tipo</label>
                    <select x-model="filters.type"
                        class="block w-full border-neutral-300 rounded-lg focus:border-brand-500 focus:ring-brand-500">
                        <option value="">Tudo</option>
                        <option value="entrada">Entradas</option>
                        <option value="saida">Saídas</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-2 mt-4">
                <button type="button" @click="applyFilters()" class="btn-primary">Aplicar filtros</button>
                <button type="button" @click="clearFilters()" x-show="hasActiveFilters" class="btn-secondary">Limpar</button>
            </div>
        </x-card>

        {{-- Carregando --}}
        <div x-show="loading" class="text-center text-neutral-400 dark:text-neutral-500 py-10">Carregando transações…</div>

        {{-- Vazio --}}
        <div x-show="!loading && items.length === 0" class="glass text-center py-12">
            <p class="text-neutral-500 dark:text-neutral-400">Nenhuma transação encontrada.</p>
            <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-quick-add'))" class="btn-primary mt-4">
                + Registrar a primeira
            </button>
        </div>

        {{-- Lista --}}
        <div x-show="!loading && items.length > 0" class="space-y-2.5">
            <template x-for="tx in items" :key="tx.id">
                <div class="glass-row shadow-card p-4 flex items-center gap-3.5">
                    {{-- Ícone da categoria (quadrado arredondado, em destaque) --}}
                    <span class="flex items-center justify-center w-12 h-12 rounded-2xl shrink-0"
                          :style="`background:${tx.category?.color || '#6B7280'}1F; color:${tx.category?.color || '#6B7280'}`"
                          x-html="iconFor(tx)"></span>

                    {{-- Descrição + metadados --}}
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-[15px] leading-tight text-neutral-800 dark:text-neutral-100 truncate" x-text="tx.description || categoryName(tx)"></p>
                        <div class="mt-1.5 flex flex-wrap items-center gap-1.5 text-xs text-neutral-500 dark:text-neutral-400">
                            <span class="truncate" x-text="categoryName(tx)"></span>
                            <span class="text-neutral-300 dark:text-neutral-600">·</span>
                            <span x-text="dateBR(tx.date)"></span>
                            <template x-if="tx.classification">
                                <span class="px-2 py-0.5 rounded-full bg-neutral-100/80 dark:bg-neutral-700/50 text-neutral-600 dark:text-neutral-300 capitalize" x-text="tx.classification"></span>
                            </template>
                        </div>
                    </div>

                    {{-- Valor + ações (empilhados, com respiro) --}}
                    <div class="shrink-0 flex flex-col items-end gap-1.5">
                        <p class="font-bold text-base whitespace-nowrap"
                           :class="tx.type === 'entrada' ? 'text-emerald-600 dark:text-emerald-400' : 'text-danger'"
                           x-text="(tx.type === 'entrada' ? '+ ' : '- ') + money(tx.amount)"></p>

                        <template x-if="confirmingId !== tx.id">
                            <div class="flex items-center gap-0.5 -mr-1.5">
                                <button type="button" @click="editTransaction(tx)" class="p-1.5 rounded-lg text-neutral-400 hover:text-brand-600 hover:bg-white/60 dark:hover:bg-white/10 transition" aria-label="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" /></svg>
                                </button>
                                <button type="button" @click="confirmDelete(tx.id)" class="p-1.5 rounded-lg text-neutral-400 hover:text-danger hover:bg-white/60 dark:hover:bg-white/10 transition" aria-label="Excluir">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                </button>
                            </div>
                        </template>
                        {{-- Confirmação inline de exclusão --}}
                        <template x-if="confirmingId === tx.id">
                            <div class="flex items-center gap-1">
                                <button type="button" @click="remove(tx.id)" :disabled="deleting" class="btn-danger px-2.5 py-1 text-xs">Excluir</button>
                                <button type="button" @click="cancelDelete()" class="btn-secondary px-2.5 py-1 text-xs">Cancelar</button>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        {{-- Paginação --}}
        <div x-show="!loading && items.length > 0" class="flex items-center justify-between pt-2">
            <button type="button" @click="prev()" :disabled="!canPrev" class="btn-secondary" :class="{ 'opacity-50 cursor-not-allowed': !canPrev }">
                Anterior
            </button>
            <span class="text-sm text-neutral-500 dark:text-neutral-400">
                Página <span x-text="meta.current_page || 1"></span> de <span x-text="meta.last_page || 1"></span>
            </span>
            <button type="button" @click="next()" :disabled="!canNext" class="btn-secondary" :class="{ 'opacity-50 cursor-not-allowed': !canNext }">
                Próxima
            </button>
        </div>
    </div>
</x-app-layout>
