{{-- Consciência (Pilar 2): top 3 maiores saídas, linha do tempo diária de gastos
     e comparativo mês a mês. Dados de /api/insights (valores em centavos).
     Mesma linguagem visual do dashboard (vidro fosco, ranking com barras). --}}
<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="font-display text-xl font-bold text-neutral-800 dark:text-neutral-100">Consciência</h1>
            <p class="text-sm text-neutral-500 dark:text-neutral-400">Para onde seu dinheiro está indo</p>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-5" x-data="insights" x-cloak>

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
        <div x-show="loading" class="glass p-10 text-center text-neutral-500 dark:text-neutral-400">Carregando seus insights…</div>

        {{-- Erro --}}
        <div x-show="!loading && error" class="glass p-8 text-center">
            <p class="text-danger" x-text="error"></p>
            <button type="button" @click="load()" class="btn-secondary mt-4">Tentar de novo</button>
        </div>

        <template x-if="!loading && !error">
            <div class="space-y-5">

                {{-- Top 3 maiores gastos --}}
                <x-card title="Seus 3 maiores gastos" subtitle="As saídas de maior valor no mês">
                    <div x-show="hasTopExpenses" class="space-y-3">
                        <template x-for="exp in topExpenses" :key="exp.id">
                            <div class="flex items-center gap-3">
                                {{-- Posição no ranking --}}
                                <span class="flex items-center justify-center w-6 h-6 rounded-full shrink-0 text-xs font-bold bg-neutral-100/80 dark:bg-neutral-700/50 text-neutral-500 dark:text-neutral-300" x-text="exp.rank"></span>
                                {{-- Ícone/cor da categoria --}}
                                <span class="flex items-center justify-center w-9 h-9 rounded-xl shrink-0"
                                      :style="`background-color: ${exp.color}1F; color: ${exp.color}`"
                                      x-html="icon(exp.icon)"></span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-baseline justify-between gap-2">
                                        <span class="font-medium text-[15px] text-neutral-800 dark:text-neutral-100 truncate" x-text="exp.description || exp.categoryName"></span>
                                        <span class="font-bold text-neutral-800 dark:text-neutral-100 whitespace-nowrap" x-text="exp.moneyLabel"></span>
                                    </div>
                                    <div class="mt-0.5 flex items-center gap-1.5 text-xs text-neutral-500 dark:text-neutral-400">
                                        <span class="truncate" x-text="exp.categoryName"></span>
                                        <span class="text-neutral-300 dark:text-neutral-600">·</span>
                                        <span x-text="exp.dateLabel"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div x-show="!hasTopExpenses" class="text-center py-8 text-neutral-500 dark:text-neutral-400">
                        Nenhuma saída registrada neste mês.
                    </div>
                </x-card>

                {{-- Linha do tempo diária --}}
                <x-card title="Gastos dia a dia" subtitle="Total de saídas em cada dia do mês">
                    <div x-show="hasTimeline">
                        <div class="flex items-end gap-[3px] h-32 overflow-x-auto pb-1">
                            <template x-for="d in timeline" :key="d.date">
                                <div class="group relative flex-1 min-w-[7px] flex flex-col justify-end h-full"
                                     :title="`Dia ${d.day}: ${d.moneyLabel}`">
                                    <div class="w-full rounded-t-md transition-all duration-500"
                                         :class="d.hasValue ? 'bg-gradient-brand' : 'bg-neutral-200/70 dark:bg-neutral-700/40'"
                                         :style="`height: ${d.hasValue ? Math.max(d.pct, 4) : 4}%`"></div>
                                </div>
                            </template>
                        </div>
                        <div class="mt-1.5 flex justify-between text-[10px] text-neutral-400 dark:text-neutral-500">
                            <span>Dia 1</span>
                            <span x-text="'Dia ' + (timeline.length || 0)"></span>
                        </div>
                    </div>
                    <div x-show="!hasTimeline" class="text-center py-8 text-neutral-500 dark:text-neutral-400">
                        Sem saídas registradas para mostrar a linha do tempo.
                    </div>
                </x-card>

                {{-- Comparativo mês a mês --}}
                <x-card title="Comparado ao mês anterior" :subtitle="null">
                    <template x-if="comparison">
                        <div>
                            <p class="text-sm text-neutral-500 dark:text-neutral-400 mb-3">
                                Mês atual vs. <span class="capitalize" x-text="previousMonthLabel"></span>
                            </p>
                            <ul class="space-y-3">
                                <template x-for="row in comparisonRows" :key="row.key">
                                    <li class="glass-row p-3 flex items-center justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400" x-text="row.label"></p>
                                            <p class="font-bold text-neutral-800 dark:text-neutral-100" x-text="row.currentLabel"></p>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <p class="font-semibold whitespace-nowrap" :class="variationClass(row.pct, row.goodWhenUp)">
                                                <span x-text="variationArrow(row.pct)"></span>
                                                <span x-text="variationLabel(row.pct)"></span>
                                            </p>
                                            <p class="text-xs text-neutral-400 dark:text-neutral-500 whitespace-nowrap">antes <span x-text="row.previousLabel"></span></p>
                                        </div>
                                    </li>
                                </template>
                            </ul>
                            <p class="text-xs text-neutral-400 dark:text-neutral-500 pt-3">
                                Em "Saiu", uma seta verde para baixo é bom: você gastou menos que no mês anterior.
                            </p>
                        </div>
                    </template>
                </x-card>

            </div>
        </template>
    </div>
</x-app-layout>
