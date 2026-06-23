{{--
    Formulário global de transação (registro rápido ≤3 toques e edição).
    Inclua UMA vez no app shell. Abre via eventos de janela:
      - open-quick-add            → novo registro (botão "+")
      - edit-transaction (detail) → edição (a partir do histórico)
    Ao salvar com sucesso, emite `transaction-saved`.

    Caminho de ≤3 toques (tipo "saída" e classificação já vêm pré-selecionados):
      1) digitar o valor  2) tocar na categoria  3) tocar em "Salvar".
--}}
<div
    x-data="transactionForm"
    x-on:open-quick-add.window="openCreate()"
    x-on:edit-transaction.window="openEdit($event.detail)"
    x-cloak
>
    {{-- Backdrop --}}
    <div
        x-show="open"
        x-transition.opacity
        class="fixed inset-0 z-40 bg-neutral-900/40"
        @click="close()"
    ></div>

    {{-- Painel: bottom-sheet no mobile, modal centralizado no desktop --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-full sm:translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-full sm:translate-y-4"
        class="fixed z-50 inset-x-0 bottom-0 sm:inset-0 sm:flex sm:items-center sm:justify-center sm:p-4"
        role="dialog" aria-modal="true"
    >
        <div class="bg-white w-full sm:max-w-lg rounded-t-3xl sm:rounded-2xl shadow-xl max-h-[92vh] overflow-y-auto">
            {{-- Cabeçalho --}}
            <div class="sticky top-0 bg-white px-5 pt-4 pb-3 border-b border-neutral-100 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-neutral-800" x-text="editingId ? 'Editar transação' : 'Nova transação'"></h2>
                <button type="button" @click="close()" class="text-neutral-400 hover:text-neutral-600 text-2xl leading-none" aria-label="Fechar">&times;</button>
            </div>

            <form @submit.prevent="save()" class="px-5 py-4 space-y-5">
                {{-- Tipo: saída / entrada --}}
                <div class="grid grid-cols-2 gap-2 p-1 bg-neutral-100 rounded-xl">
                    <button type="button" @click="setType('saida')"
                        class="py-2 rounded-lg text-sm font-semibold transition"
                        :class="form.type === 'saida' ? 'bg-white text-danger shadow-sm' : 'text-neutral-500'">
                        Saída
                    </button>
                    <button type="button" @click="setType('entrada')"
                        class="py-2 rounded-lg text-sm font-semibold transition"
                        :class="form.type === 'entrada' ? 'bg-white text-emerald-600 shadow-sm' : 'text-neutral-500'">
                        Entrada
                    </button>
                </div>

                {{-- Valor --}}
                <div>
                    <label class="block font-medium text-sm text-neutral-700 mb-1">Valor</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-neutral-500 font-semibold">R$</span>
                        <input
                            x-ref="amount"
                            type="text"
                            inputmode="numeric"
                            :value="form.amount"
                            @input="onAmountInput($event)"
                            placeholder="0,00"
                            class="block w-full pl-11 pr-4 py-3 text-2xl font-bold text-neutral-900 border-neutral-300 rounded-xl focus:border-brand-500 focus:ring-brand-500"
                            :class="{ 'border-danger focus:border-danger focus:ring-danger': fieldError('amount') }"
                        >
                    </div>
                    <p x-show="fieldError('amount')" x-text="fieldError('amount')" class="text-sm text-danger mt-1"></p>
                </div>

                {{-- Categoria --}}
                <div>
                    <label class="block font-medium text-sm text-neutral-700 mb-2">Categoria</label>

                    <div x-show="loadingCategories" class="text-sm text-neutral-400 py-2">Carregando categorias…</div>

                    <div x-show="!loadingCategories" class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                        <template x-for="cat in categories" :key="cat.id">
                            <button type="button" @click="selectCategory(cat.id)"
                                class="flex flex-col items-center gap-1 p-2 rounded-xl border text-center transition"
                                :class="form.category_id === cat.id ? 'border-brand-500 bg-brand-50' : 'border-neutral-200 hover:border-neutral-300'">
                                <span class="flex items-center justify-center w-9 h-9 rounded-full"
                                      :style="`background:${cat.color || '#6B7280'}1A; color:${cat.color || '#6B7280'}`"
                                      x-html="iconFor(cat)"></span>
                                <span class="text-xs text-neutral-700 leading-tight line-clamp-2" x-text="cat.name"></span>
                            </button>
                        </template>
                    </div>
                    <p x-show="fieldError('category_id')" x-text="fieldError('category_id')" class="text-sm text-danger mt-1"></p>
                </div>

                {{-- Classificação (apenas saída) --}}
                <div x-show="form.type === 'saida'">
                    <label class="block font-medium text-sm text-neutral-700 mb-1">Essa saída é uma…</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" @click="form.classification = 'necessidade'"
                            class="py-2 rounded-lg text-sm font-medium border transition"
                            :class="form.classification === 'necessidade' ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-neutral-200 text-neutral-500'">
                            Necessidade
                        </button>
                        <button type="button" @click="form.classification = 'desejo'"
                            class="py-2 rounded-lg text-sm font-medium border transition"
                            :class="form.classification === 'desejo' ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-neutral-200 text-neutral-500'">
                            Desejo
                        </button>
                    </div>
                </div>

                {{-- Mais opções (data, descrição, recorrência) --}}
                <div>
                    <button type="button" @click="showMore = !showMore"
                        class="text-sm font-medium text-brand-600 hover:text-brand-700 flex items-center gap-1">
                        <span x-text="showMore ? 'Menos opções' : 'Mais opções (data, descrição)'"></span>
                    </button>

                    <div x-show="showMore" x-transition class="space-y-4 mt-3">
                        <div>
                            <label class="block font-medium text-sm text-neutral-700 mb-1">Data</label>
                            <input type="date" x-model="form.date"
                                class="block w-full border-neutral-300 rounded-lg focus:border-brand-500 focus:ring-brand-500"
                                :class="{ 'border-danger': fieldError('date') }">
                            <p x-show="fieldError('date')" x-text="fieldError('date')" class="text-sm text-danger mt-1"></p>
                        </div>
                        <div>
                            <label class="block font-medium text-sm text-neutral-700 mb-1">Descrição (opcional)</label>
                            <input type="text" x-model="form.description" maxlength="255" placeholder="Ex.: Mercado da semana"
                                class="block w-full border-neutral-300 rounded-lg focus:border-brand-500 focus:ring-brand-500"
                                :class="{ 'border-danger': fieldError('description') }">
                            <p x-show="fieldError('description')" x-text="fieldError('description')" class="text-sm text-danger mt-1"></p>
                        </div>
                        <label class="flex items-center gap-2 text-sm text-neutral-700">
                            <input type="checkbox" x-model="form.is_recurring"
                                class="rounded border-neutral-300 text-brand-600 focus:ring-brand-500">
                            É uma transação que se repete todo mês
                        </label>
                    </div>
                </div>

                {{-- Salvar --}}
                <div class="pt-2">
                    <button type="submit" :disabled="!canSave"
                        class="btn-primary w-full justify-center py-3 text-base">
                        <svg x-show="saving" class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="saving ? 'Salvando…' : (editingId ? 'Salvar alterações' : 'Salvar')"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
