{{-- Economia (Pilar 3): meta de economia mensal, orçamentos semafóricos por
     categoria, detector de gastos invisíveis e relatório "onde economizar".
     Dados de /api/savings-goal, /api/budgets/status, /api/insights/invisible e
     /api/savings-report (valores em centavos). Linguagem visual do dashboard. --}}
<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="font-display text-xl font-bold text-neutral-800 dark:text-neutral-100">Economia</h1>
            <p class="text-sm text-neutral-500 dark:text-neutral-400">Metas, limites e onde cortar gastos</p>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-5" x-data="economia" x-cloak>

        {{-- Seletor de mês (pílula de vidro) --}}
        <div class="flex items-center justify-center">
            <div class="glass inline-flex items-center gap-1 px-1.5 py-1 !rounded-full">
                <button type="button" @click="prevMonth()" class="p-1.5 rounded-full text-neutral-500 dark:text-neutral-300 hover:bg-white/60 dark:hover:bg-white/10 transition" aria-label="Mês anterior">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                </button>
                <span class="text-sm font-semibold text-neutral-700 dark:text-neutral-100 capitalize min-w-[8.5rem] text-center" x-text="monthLabel"></span>
                <button type="button" @click="nextMonth()" :disabled="!canGoNext"
                        class="p-1.5 rounded-full text-neutral-500 dark:text-neutral-300 hover:bg-white/60 dark:hover:bg-white/10 transition" :class="{ 'opacity-30 cursor-not-allowed': !canGoNext }" aria-label="Próximo mês">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                </button>
            </div>
        </div>

        {{-- Carregando --}}
        <div x-show="loading" class="glass p-10 text-center text-neutral-500 dark:text-neutral-400">Carregando…</div>

        {{-- Erro --}}
        <div x-show="!loading && error" class="glass p-8 text-center">
            <p class="text-danger" x-text="error"></p>
            <button type="button" @click="load()" class="btn-secondary mt-4">Tentar de novo</button>
        </div>

        <template x-if="!loading && !error">
            <div class="space-y-5">

                {{-- Meta de economia mensal --}}
                <x-card>
                    <x-slot name="action">
                        <button type="button" @click="startGoalEdit()" class="text-sm font-medium text-brand-600 dark:text-brand-300 hover:underline" x-text="hasGoal ? 'Alterar' : 'Definir meta'"></button>
                    </x-slot>
                    <div class="mb-1">
                        <h3 class="text-base font-semibold text-neutral-800 dark:text-neutral-100">Meta de economia</h3>
                        <p class="text-sm text-neutral-500 dark:text-neutral-400 mt-0.5">Quanto você quer guardar neste mês</p>
                    </div>

                    {{-- Sem meta definida --}}
                    <template x-if="!hasGoal && !goalForm.open">
                        <div class="text-center py-6">
                            <p class="text-neutral-500 dark:text-neutral-400">Você ainda não definiu uma meta de economia.</p>
                            <button type="button" @click="startGoalEdit()" class="btn-primary mt-4">Definir uma meta</button>
                        </div>
                    </template>

                    {{-- Com meta --}}
                    <template x-if="hasGoal && !goalForm.open">
                        <div class="mt-3 space-y-3">
                            <div class="flex items-baseline justify-between gap-2">
                                <span class="money text-2xl font-bold tracking-tight text-neutral-800 dark:text-neutral-100" x-text="goalView.savedLabel"></span>
                                <span class="text-sm text-neutral-500 dark:text-neutral-400">de <span x-text="goalView.goalLabel"></span></span>
                            </div>
                            <div class="w-full h-2.5 bg-neutral-200 dark:bg-neutral-700/50 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500 ease-out"
                                     :class="goalView.achieved ? 'bg-gradient-emerald' : 'bg-gradient-brand'"
                                     :style="`width: ${goalView.width}%`"></div>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <template x-if="goalView.achieved">
                                    <span class="inline-flex items-center gap-1.5 font-semibold text-emerald-600 dark:text-emerald-400">
                                        🎉 Meta atingida!
                                    </span>
                                </template>
                                <template x-if="!goalView.achieved">
                                    <span class="text-neutral-500 dark:text-neutral-400">Faltam <span class="font-semibold text-neutral-700 dark:text-neutral-200" x-text="goalView.remainingLabel"></span></span>
                                </template>
                                <span class="font-semibold text-neutral-700 dark:text-neutral-200" x-text="goalView.pct + '%'"></span>
                            </div>
                            <template x-if="!goalView.savedPositive">
                                <p class="text-xs text-danger">Suas saídas passaram das entradas neste mês, então ainda não há sobra para guardar.</p>
                            </template>
                        </div>
                    </template>

                    {{-- Formulário da meta --}}
                    <template x-if="goalForm.open">
                        <div class="mt-3 space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Meta do mês (R$)</label>
                                <input type="text" inputmode="decimal" :value="goalForm.amount" @input="onGoalInput($event)" placeholder="0,00"
                                    class="block w-full border-neutral-300 rounded-lg focus:border-brand-500 focus:ring-brand-500">
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" @click="saveGoal()" :disabled="savingGoal" class="btn-primary">
                                    <span x-text="savingGoal ? 'Salvando…' : 'Salvar meta'"></span>
                                </button>
                                <button type="button" @click="cancelGoal()" class="btn-secondary">Cancelar</button>
                                <button type="button" x-show="hasGoal" @click="clearGoal()" :disabled="savingGoal" class="text-sm font-medium text-danger hover:underline ml-auto self-center">Remover meta</button>
                            </div>
                        </div>
                    </template>
                </x-card>

                {{-- Orçamentos por categoria --}}
                <x-card>
                    <x-slot name="action">
                        <button type="button" @click="startBudgetCreate()" x-show="availableCategories.length > 0 && !budgetForm.open" class="text-sm font-medium text-brand-600 dark:text-brand-300 hover:underline">+ Novo limite</button>
                    </x-slot>
                    <div class="mb-3">
                        <h3 class="text-base font-semibold text-neutral-800 dark:text-neutral-100">Orçamentos por categoria</h3>
                        <p class="text-sm text-neutral-500 dark:text-neutral-400 mt-0.5">Defina um limite de gastos e acompanhe o quanto já usou</p>
                    </div>

                    {{-- Formulário de orçamento (criar/editar) --}}
                    <template x-if="budgetForm.open">
                        <div class="glass-row p-3 mb-3 space-y-3">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Categoria</label>
                                    {{-- Ao editar, a categoria é fixa; ao criar, escolhe entre as sem orçamento. --}}
                                    <template x-if="budgetForm.id">
                                        <input type="text" disabled
                                            :value="(budgets.find(b => b.id === budgetForm.id)?.category?.name) || ''"
                                            class="block w-full border-neutral-300 rounded-lg bg-neutral-100 dark:bg-neutral-800 text-neutral-500">
                                    </template>
                                    <template x-if="!budgetForm.id">
                                        <select x-model="budgetForm.category_id"
                                            class="block w-full border-neutral-300 rounded-lg focus:border-brand-500 focus:ring-brand-500"
                                            :class="{ 'border-danger': budgetError('category_id') }">
                                            <option value="">Escolha uma categoria</option>
                                            <template x-for="cat in availableCategories" :key="cat.id">
                                                <option :value="cat.id" x-text="cat.name"></option>
                                            </template>
                                        </select>
                                    </template>
                                    <p x-show="budgetError('category_id')" x-text="budgetError('category_id')" class="text-sm text-danger mt-1"></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Limite mensal (R$)</label>
                                    <input type="text" inputmode="decimal" :value="budgetForm.limit" @input="onBudgetLimitInput($event)" placeholder="0,00"
                                        class="block w-full border-neutral-300 rounded-lg focus:border-brand-500 focus:ring-brand-500"
                                        :class="{ 'border-danger': budgetError('monthly_limit') }">
                                    <p x-show="budgetError('monthly_limit')" x-text="budgetError('monthly_limit')" class="text-sm text-danger mt-1"></p>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" @click="saveBudget()" :disabled="!canSaveBudget" class="btn-primary">
                                    <span x-text="savingBudget ? 'Salvando…' : 'Salvar'"></span>
                                </button>
                                <button type="button" @click="cancelBudget()" class="btn-secondary">Cancelar</button>
                            </div>
                        </div>
                    </template>

                    {{-- Lista de orçamentos --}}
                    <div x-show="hasBudgets" class="space-y-3">
                        <template x-for="b in budgetRows" :key="b.id">
                            <div class="glass-row p-3">
                                <div class="flex items-center gap-3">
                                    <span class="flex items-center justify-center w-9 h-9 rounded-xl shrink-0"
                                          :style="`background-color: ${b.color}1F; color: ${b.color}`"
                                          x-html="icon(b.icon)"></span>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="font-medium text-[15px] text-neutral-800 dark:text-neutral-100 truncate" x-text="b.categoryName"></span>
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold shrink-0" :class="b.badgeClass">
                                                <span x-text="b.statusLabel"></span>
                                            </span>
                                        </div>
                                        <div class="mt-1.5 w-full h-2 rounded-full overflow-hidden bg-neutral-200/70 dark:bg-neutral-700/50">
                                            <div class="h-full rounded-full transition-all duration-500" :class="b.barClass" :style="`width: ${b.width}%`"></div>
                                        </div>
                                        <div class="mt-1.5 flex items-center justify-between text-xs text-neutral-500 dark:text-neutral-400">
                                            <span><span class="font-medium text-neutral-700 dark:text-neutral-200" x-text="b.consumedLabel"></span> de <span x-text="b.limitLabel"></span></span>
                                            <span x-show="!b.overspent">Resta <span class="font-medium" x-text="b.remainingLabel"></span></span>
                                            <span x-show="b.overspent" class="text-danger font-medium">Passou <span x-text="b.remainingLabel"></span></span>
                                        </div>
                                    </div>
                                </div>
                                {{-- Ações --}}
                                <div class="mt-2 flex items-center justify-end gap-1">
                                    <template x-if="confirmingBudgetId !== b.id">
                                        <div class="flex items-center gap-1">
                                            <button type="button" @click="startBudgetEdit(b)" class="px-2.5 py-1 text-xs rounded-lg text-neutral-500 hover:text-brand-600 hover:bg-white/60 dark:hover:bg-white/10 transition">Editar</button>
                                            <button type="button" @click="confirmDeleteBudget(b.id)" class="px-2.5 py-1 text-xs rounded-lg text-neutral-500 hover:text-danger hover:bg-white/60 dark:hover:bg-white/10 transition">Excluir</button>
                                        </div>
                                    </template>
                                    <template x-if="confirmingBudgetId === b.id">
                                        <div class="flex items-center gap-1">
                                            <button type="button" @click="removeBudget(b.id)" class="btn-danger px-2.5 py-1 text-xs">Excluir</button>
                                            <button type="button" @click="cancelDeleteBudget()" class="btn-secondary px-2.5 py-1 text-xs">Cancelar</button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Vazio --}}
                    <div x-show="!hasBudgets && !budgetForm.open" class="text-center py-6">
                        <p class="text-neutral-500 dark:text-neutral-400">Você ainda não definiu limites por categoria.</p>
                        <button type="button" @click="startBudgetCreate()" class="btn-primary mt-4">+ Criar o primeiro limite</button>
                    </div>
                </x-card>

                {{-- Gastos invisíveis --}}
                <x-card title="Gastos invisíveis" subtitle="Assinaturas e contas fixas que pesam todo mês">
                    <template x-if="hasInvisible">
                        <div class="space-y-4">
                            <div class="glass-row p-3 flex items-baseline justify-between">
                                <span class="text-sm text-neutral-500 dark:text-neutral-400">Impacto somado por mês</span>
                                <span class="money text-lg font-bold tracking-tight text-neutral-800 dark:text-neutral-100" x-text="invisibleTotalLabel"></span>
                            </div>
                            <ul class="space-y-3">
                                <template x-for="it in invisibleItems" :key="it.id">
                                    <li class="flex items-center gap-3">
                                        <span class="flex items-center justify-center w-9 h-9 rounded-xl shrink-0"
                                              :style="`background-color: ${it.color}1F; color: ${it.color}`"
                                              x-html="icon(it.icon)"></span>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-baseline justify-between gap-2">
                                                <span class="font-medium text-[15px] text-neutral-800 dark:text-neutral-100 truncate" x-text="it.description || it.categoryName"></span>
                                                <span class="font-bold text-neutral-800 dark:text-neutral-100 whitespace-nowrap" x-text="it.impactLabel"></span>
                                            </div>
                                            <div class="mt-0.5 flex items-center gap-1.5 text-xs text-neutral-500 dark:text-neutral-400">
                                                <span class="truncate" x-text="it.categoryName"></span>
                                                <span class="text-neutral-300 dark:text-neutral-600">·</span>
                                                <span x-text="it.frequencyLabel"></span>
                                            </div>
                                        </div>
                                    </li>
                                </template>
                            </ul>
                            <p class="text-xs text-neutral-400 dark:text-neutral-500">Valores convertidos para o equivalente por mês, para você enxergar o peso real.</p>
                        </div>
                    </template>
                    <template x-if="!hasInvisible">
                        <p class="text-center text-neutral-500 dark:text-neutral-400 py-6">Nenhuma conta fixa cadastrada. Marque uma transação como recorrente para vê-la aqui.</p>
                    </template>
                </x-card>

                {{-- Onde economizar --}}
                <x-card title="Onde economizar" subtitle="Sugestões para sobrar mais no fim do mês">
                    <template x-if="hasSuggestions">
                        <div class="space-y-4">
                            <div class="glass-row p-3 flex items-baseline justify-between">
                                <span class="text-sm text-neutral-500 dark:text-neutral-400">Economia possível por mês</span>
                                <span class="money text-lg font-bold tracking-tight text-emerald-600 dark:text-emerald-400" x-text="potentialSavingsLabel"></span>
                            </div>
                            <ul class="space-y-2.5">
                                <template x-for="(s, i) in suggestions" :key="i">
                                    <li class="flex items-start gap-3">
                                        <span class="flex items-center justify-center w-9 h-9 rounded-xl shrink-0"
                                              :style="`background-color: ${s.color}1F; color: ${s.color}`"
                                              x-html="icon(s.icon)"></span>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-[15px] text-neutral-800 dark:text-neutral-100" x-text="s.message"></p>
                                            <p class="mt-0.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400">Economia: <span x-text="s.savingsLabel"></span></p>
                                        </div>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </template>
                    <template x-if="!hasSuggestions">
                        <p class="text-center text-neutral-500 dark:text-neutral-400 py-6">Sem sugestões por enquanto. Continue registrando suas saídas para receber dicas de economia.</p>
                    </template>
                </x-card>

            </div>
        </template>
    </div>
</x-app-layout>
