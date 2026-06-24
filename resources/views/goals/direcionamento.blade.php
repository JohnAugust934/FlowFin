{{-- Direcionamento (Pilar 5): metas com propósito e progresso, simulador
     interativo, prioridades e investimentos. Dados de /api/goals (+ /simulate) e
     /api/investments (valores em centavos). Linguagem visual do dashboard. --}}
<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-bold text-neutral-800 dark:text-neutral-100">Direcionamento</h1>
            <p class="text-sm text-neutral-500 dark:text-neutral-400">Metas com propósito, simulações e investimentos</p>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-5" x-data="direcionamento" x-cloak>

        {{-- Carregando --}}
        <div x-show="loading" class="glass p-10 text-center text-neutral-500 dark:text-neutral-400">Carregando suas metas…</div>

        {{-- Erro --}}
        <div x-show="!loading && error" class="glass p-8 text-center">
            <p class="text-danger" x-text="error"></p>
            <button type="button" @click="load()" class="btn-secondary mt-4">Tentar de novo</button>
        </div>

        <template x-if="!loading && !error">
            <div class="space-y-5">

                {{-- Resumo de prioridades --}}
                <div class="grid grid-cols-3 gap-3" x-show="hasGoals">
                    <template x-for="p in priorityCounts" :key="p.key">
                        <div class="glass p-3 text-center">
                            <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold" :class="p.class" x-text="p.label"></span>
                            <p class="mt-1.5 text-2xl font-extrabold text-neutral-800 dark:text-neutral-100" x-text="p.count"></p>
                            <p class="text-xs text-neutral-400 dark:text-neutral-500" x-text="p.count === 1 ? 'meta' : 'metas'"></p>
                        </div>
                    </template>
                </div>

                {{-- Metas com propósito --}}
                <x-card>
                    <x-slot name="action">
                        <button type="button" @click="startGoalCreate()" x-show="!goalForm.open" class="text-sm font-medium text-brand-600 dark:text-brand-300 hover:underline">+ Nova meta</button>
                    </x-slot>
                    <div class="mb-3">
                        <h3 class="text-base font-semibold text-neutral-800 dark:text-neutral-100">Metas com propósito</h3>
                        <p class="text-sm text-neutral-500 dark:text-neutral-400 mt-0.5">Defina aonde quer chegar e acompanhe o progresso</p>
                    </div>

                    {{-- Formulário de meta (criar/editar) --}}
                    <template x-if="goalForm.open">
                        <div class="glass-row p-3 mb-4 space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Nome da meta</label>
                                <input type="text" x-model="goalForm.name" placeholder="Ex.: Reserva de emergência"
                                    class="block w-full border-neutral-300 rounded-lg focus:border-brand-500 focus:ring-brand-500"
                                    :class="{ 'border-danger': goalError('name') }">
                                <p x-show="goalError('name')" x-text="goalError('name')" class="text-sm text-danger mt-1"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Propósito <span class="text-neutral-400 font-normal">(opcional)</span></label>
                                <textarea x-model="goalForm.description" rows="2" placeholder="Por que essa meta é importante para você?"
                                    class="block w-full border-neutral-300 rounded-lg focus:border-brand-500 focus:ring-brand-500"></textarea>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Valor-alvo (R$)</label>
                                    <input type="text" inputmode="decimal" :value="goalForm.target" @input="onGoalTargetInput($event)" placeholder="0,00"
                                        class="block w-full border-neutral-300 rounded-lg focus:border-brand-500 focus:ring-brand-500"
                                        :class="{ 'border-danger': goalError('target_amount') }">
                                    <p x-show="goalError('target_amount')" x-text="goalError('target_amount')" class="text-sm text-danger mt-1"></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Já guardei (R$)</label>
                                    <input type="text" inputmode="decimal" :value="goalForm.saved" @input="onGoalSavedInput($event)" placeholder="0,00"
                                        class="block w-full border-neutral-300 rounded-lg focus:border-brand-500 focus:ring-brand-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Prazo <span class="text-neutral-400 font-normal">(opcional)</span></label>
                                    <input type="date" x-model="goalForm.due_date"
                                        class="block w-full border-neutral-300 rounded-lg focus:border-brand-500 focus:ring-brand-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Prioridade</label>
                                    <select x-model="goalForm.priority" class="block w-full border-neutral-300 rounded-lg focus:border-brand-500 focus:ring-brand-500">
                                        <option value="alta">Alta</option>
                                        <option value="media">Média</option>
                                        <option value="baixa">Baixa</option>
                                    </select>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" @click="saveGoal()" :disabled="!canSaveGoal" class="btn-primary">
                                    <span x-text="savingGoal ? 'Salvando…' : 'Salvar meta'"></span>
                                </button>
                                <button type="button" @click="cancelGoalForm()" class="btn-secondary">Cancelar</button>
                            </div>
                        </div>
                    </template>

                    {{-- Lista de metas --}}
                    <div x-show="hasGoals" class="space-y-3">
                        <template x-for="g in goalRows" :key="g.id">
                            <div class="glass-row p-4">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="font-semibold text-[15px] text-neutral-800 dark:text-neutral-100 truncate" x-text="g.name"></p>
                                        <p x-show="g.description" class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5" x-text="g.description"></p>
                                    </div>
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold shrink-0" :class="g.priorityClass" x-text="g.priorityLabel"></span>
                                </div>
                                <div class="mt-3 w-full h-2.5 rounded-full overflow-hidden bg-neutral-200/70 dark:bg-neutral-700/50">
                                    <div class="h-full rounded-full transition-all duration-500" :class="g.achieved ? 'bg-gradient-emerald' : 'bg-gradient-brand'" :style="`width: ${g.width}%`"></div>
                                </div>
                                <div class="mt-1.5 flex items-center justify-between text-xs text-neutral-500 dark:text-neutral-400">
                                    <span><span class="font-medium text-neutral-700 dark:text-neutral-200" x-text="g.savedLabel"></span> de <span x-text="g.targetLabel"></span></span>
                                    <span class="font-semibold" :class="g.achieved ? 'text-emerald-600 dark:text-emerald-400' : 'text-neutral-600 dark:text-neutral-300'" x-text="g.pctLabel"></span>
                                </div>
                                <div class="mt-1 flex items-center justify-between text-xs text-neutral-400 dark:text-neutral-500">
                                    <span x-show="!g.achieved">Faltam <span class="font-medium text-neutral-600 dark:text-neutral-300" x-text="g.remainingLabel"></span></span>
                                    <span x-show="g.achieved" class="font-semibold text-emerald-600 dark:text-emerald-400">🎉 Meta atingida!</span>
                                    <span x-show="g.dueDateLabel">Prazo: <span x-text="g.dueDateLabel"></span></span>
                                </div>
                                {{-- Ações --}}
                                <div class="mt-2 flex items-center justify-end gap-1">
                                    <template x-if="confirmingGoalId !== g.id">
                                        <div class="flex items-center gap-1">
                                            <button type="button" @click="startGoalEdit(g)" class="px-2.5 py-1 text-xs rounded-lg text-neutral-500 hover:text-brand-600 hover:bg-white/60 dark:hover:bg-white/10 transition">Editar</button>
                                            <button type="button" @click="confirmDeleteGoal(g.id)" class="px-2.5 py-1 text-xs rounded-lg text-neutral-500 hover:text-danger hover:bg-white/60 dark:hover:bg-white/10 transition">Excluir</button>
                                        </div>
                                    </template>
                                    <template x-if="confirmingGoalId === g.id">
                                        <div class="flex items-center gap-1">
                                            <button type="button" @click="removeGoal(g.id)" class="btn-danger px-2.5 py-1 text-xs">Excluir</button>
                                            <button type="button" @click="cancelDeleteGoal()" class="btn-secondary px-2.5 py-1 text-xs">Cancelar</button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        {{-- Paginação --}}
                        <div class="flex items-center justify-between pt-2" x-show="canPrevGoals || canNextGoals">
                            <button type="button" @click="prevGoals()" :disabled="!canPrevGoals" class="btn-secondary px-3 py-1.5 text-sm" :class="{ 'opacity-40 cursor-not-allowed': !canPrevGoals }">Anterior</button>
                            <span class="text-xs text-neutral-400 dark:text-neutral-500">Página <span x-text="goalsMeta.current_page || 1"></span> de <span x-text="goalsMeta.last_page || 1"></span></span>
                            <button type="button" @click="nextGoals()" :disabled="!canNextGoals" class="btn-secondary px-3 py-1.5 text-sm" :class="{ 'opacity-40 cursor-not-allowed': !canNextGoals }">Próxima</button>
                        </div>
                    </div>

                    {{-- Vazio --}}
                    <div x-show="!hasGoals && !goalForm.open" class="text-center py-6">
                        <p class="text-neutral-500 dark:text-neutral-400">Você ainda não tem metas. Que tal definir a primeira?</p>
                        <button type="button" @click="startGoalCreate()" class="btn-primary mt-4">+ Criar minha primeira meta</button>
                    </div>
                </x-card>

                {{-- Simulador --}}
                <x-card title="Simulador de metas" subtitle="Preencha dois campos e descubra o terceiro">
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div>
                            <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Guardar por mês (R$)</label>
                            <input type="text" inputmode="decimal" :value="sim.monthly" @input="onSimMonthlyInput($event)" placeholder="0,00"
                                class="block w-full border-neutral-300 rounded-lg focus:border-brand-500 focus:ring-brand-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Valor-alvo (R$)</label>
                            <input type="text" inputmode="decimal" :value="sim.target" @input="onSimTargetInput($event)" placeholder="0,00"
                                class="block w-full border-neutral-300 rounded-lg focus:border-brand-500 focus:ring-brand-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Em quantos meses</label>
                            <input type="text" inputmode="numeric" :value="sim.months" @input="onSimMonthsInput($event)" placeholder="0"
                                class="block w-full border-neutral-300 rounded-lg focus:border-brand-500 focus:ring-brand-500">
                        </div>
                    </div>

                    <template x-if="sim.result">
                        <div class="mt-4 glass-row p-4 border border-brand-200/60 dark:border-brand-500/30">
                            <p class="text-[15px] text-neutral-800 dark:text-neutral-100 font-medium" x-text="simPhrase"></p>
                            <button type="button" @click="useSimInGoal()" class="mt-2 text-sm font-medium text-brand-600 dark:text-brand-300 hover:underline">Criar uma meta com esse valor</button>
                        </div>
                    </template>
                    <p x-show="!sim.result && sim.message" class="mt-3 text-sm text-neutral-500 dark:text-neutral-400" x-text="sim.message"></p>
                </x-card>

                {{-- Investimentos --}}
                <x-card>
                    <x-slot name="action">
                        <button type="button" @click="startInvestCreate()" x-show="!investForm.open" class="text-sm font-medium text-brand-600 dark:text-brand-300 hover:underline">+ Novo investimento</button>
                    </x-slot>
                    <div class="mb-3">
                        <h3 class="text-base font-semibold text-neutral-800 dark:text-neutral-100">Investimentos</h3>
                        <p class="text-sm text-neutral-500 dark:text-neutral-400 mt-0.5">Registre o que você já investiu</p>
                    </div>

                    {{-- Total investido --}}
                    <div class="glass-row p-3 mb-4 flex items-baseline justify-between">
                        <span class="text-sm text-neutral-500 dark:text-neutral-400">Total investido</span>
                        <span class="text-lg font-extrabold tracking-tight text-emerald-600 dark:text-emerald-400" x-text="totalInvestedLabel"></span>
                    </div>

                    {{-- Formulário de investimento (criar/editar) --}}
                    <template x-if="investForm.open">
                        <div class="glass-row p-3 mb-4 space-y-3">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Descrição</label>
                                    <input type="text" x-model="investForm.description" placeholder="Ex.: Tesouro Direto"
                                        class="block w-full border-neutral-300 rounded-lg focus:border-brand-500 focus:ring-brand-500"
                                        :class="{ 'border-danger': investError('description') }">
                                    <p x-show="investError('description')" x-text="investError('description')" class="text-sm text-danger mt-1"></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Tipo <span class="text-neutral-400 font-normal">(opcional)</span></label>
                                    <input type="text" x-model="investForm.type" placeholder="Ex.: Renda fixa"
                                        class="block w-full border-neutral-300 rounded-lg focus:border-brand-500 focus:ring-brand-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Valor (R$)</label>
                                    <input type="text" inputmode="decimal" :value="investForm.amount" @input="onInvestAmountInput($event)" placeholder="0,00"
                                        class="block w-full border-neutral-300 rounded-lg focus:border-brand-500 focus:ring-brand-500"
                                        :class="{ 'border-danger': investError('amount') }">
                                    <p x-show="investError('amount')" x-text="investError('amount')" class="text-sm text-danger mt-1"></p>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" @click="saveInvest()" :disabled="!canSaveInvest" class="btn-primary">
                                    <span x-text="savingInvest ? 'Salvando…' : 'Salvar'"></span>
                                </button>
                                <button type="button" @click="cancelInvestForm()" class="btn-secondary">Cancelar</button>
                            </div>
                        </div>
                    </template>

                    {{-- Lista de investimentos --}}
                    <div x-show="hasInvestments" class="space-y-3">
                        <template x-for="it in investmentRows" :key="it.id">
                            <div class="glass-row p-3">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="font-medium text-[15px] text-neutral-800 dark:text-neutral-100 truncate" x-text="it.description"></p>
                                        <p class="text-xs text-neutral-500 dark:text-neutral-400" x-text="it.typeLabel"></p>
                                    </div>
                                    <span class="font-bold text-neutral-800 dark:text-neutral-100 whitespace-nowrap" x-text="it.amountLabel"></span>
                                </div>
                                <div class="mt-2 flex items-center justify-end gap-1">
                                    <template x-if="confirmingInvestId !== it.id">
                                        <div class="flex items-center gap-1">
                                            <button type="button" @click="startInvestEdit(it)" class="px-2.5 py-1 text-xs rounded-lg text-neutral-500 hover:text-brand-600 hover:bg-white/60 dark:hover:bg-white/10 transition">Editar</button>
                                            <button type="button" @click="confirmDeleteInvest(it.id)" class="px-2.5 py-1 text-xs rounded-lg text-neutral-500 hover:text-danger hover:bg-white/60 dark:hover:bg-white/10 transition">Excluir</button>
                                        </div>
                                    </template>
                                    <template x-if="confirmingInvestId === it.id">
                                        <div class="flex items-center gap-1">
                                            <button type="button" @click="removeInvest(it.id)" class="btn-danger px-2.5 py-1 text-xs">Excluir</button>
                                            <button type="button" @click="cancelDeleteInvest()" class="btn-secondary px-2.5 py-1 text-xs">Cancelar</button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        {{-- Paginação --}}
                        <div class="flex items-center justify-between pt-2" x-show="canPrevInvest || canNextInvest">
                            <button type="button" @click="prevInvest()" :disabled="!canPrevInvest" class="btn-secondary px-3 py-1.5 text-sm" :class="{ 'opacity-40 cursor-not-allowed': !canPrevInvest }">Anterior</button>
                            <span class="text-xs text-neutral-400 dark:text-neutral-500">Página <span x-text="investmentsMeta.current_page || 1"></span> de <span x-text="investmentsMeta.last_page || 1"></span></span>
                            <button type="button" @click="nextInvest()" :disabled="!canNextInvest" class="btn-secondary px-3 py-1.5 text-sm" :class="{ 'opacity-40 cursor-not-allowed': !canNextInvest }">Próxima</button>
                        </div>
                    </div>

                    {{-- Vazio --}}
                    <div x-show="!hasInvestments && !investForm.open" class="text-center py-6">
                        <p class="text-neutral-500 dark:text-neutral-400">Nenhum investimento registrado ainda.</p>
                        <button type="button" @click="startInvestCreate()" class="btn-primary mt-4">+ Registrar investimento</button>
                    </div>
                </x-card>

            </div>
        </template>
    </div>
</x-app-layout>
