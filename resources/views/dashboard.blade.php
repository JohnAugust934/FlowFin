{{-- Dashboard do mês: resumo entrou/saiu/sobrou, gráfico de rosca por categoria
     e proporção necessidade vs. desejo. Dados de /api/dashboard (valores em centavos).
     Estética de vidro fosco (glassmorphism / iOS 26) sobre o fundo ambiente. --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="font-display text-xl font-bold text-neutral-800 dark:text-neutral-100">Resumo do mês</h1>
                <p class="text-sm text-neutral-500 dark:text-neutral-400">Quanto entrou, quanto saiu e o que sobrou</p>
            </div>
            <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-quick-add'))" class="btn-primary hidden sm:inline-flex">
                + Nova
            </button>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-5" x-data="dashboard" x-cloak>

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
        <div x-show="loading" class="glass p-10 text-center text-neutral-500 dark:text-neutral-400">Carregando o resumo…</div>

        {{-- Erro --}}
        <div x-show="!loading && error" class="glass p-8 text-center">
            <p class="text-danger" x-text="error"></p>
            <button type="button" @click="load()" class="btn-secondary mt-4">Tentar de novo</button>
        </div>

        <template x-if="!loading && !error">
            <div class="space-y-5">
                {{-- Painel principal: o que sobrou (figura-herói) + entrou/saiu --}}
                <div class="glass relative overflow-hidden p-6">
                    {{-- Brilho de marca no topo do vidro --}}
                    <div class="pointer-events-none absolute -top-16 -right-10 w-56 h-56 rounded-full opacity-40 blur-3xl"
                         :class="totals.sobrou >= 0 ? 'bg-emerald-400/40' : 'bg-danger/40'"></div>

                    <p class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Sobrou neste mês</p>
                    <p class="money mt-1 text-4xl sm:text-5xl font-bold tracking-tight break-words"
                       :class="totals.sobrou >= 0 ? 'text-brand-600 dark:text-brand-300' : 'text-danger'"
                       x-text="sobrouDisplayLabel"></p>
                    <p class="mt-1 text-xs text-neutral-400 dark:text-neutral-500"
                       x-text="totals.sobrou >= 0 ? 'Você fechou o mês no positivo.' : 'As saídas passaram das entradas neste mês.'"></p>

                    {{-- Chips entrou/saiu --}}
                    <div class="mt-5 grid grid-cols-2 gap-3">
                        <div class="glass-row p-3">
                            <div class="flex items-center gap-1.5 text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                <span class="flex items-center justify-center w-5 h-5 rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19V5m0 0l-6 6m6-6l6 6" /></svg>
                                </span>
                                Entrou
                            </div>
                            <p class="money mt-1 text-lg font-bold text-emerald-600 dark:text-emerald-400 break-words" x-text="money(totals.entrou)"></p>
                        </div>
                        <div class="glass-row p-3">
                            <div class="flex items-center gap-1.5 text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                <span class="flex items-center justify-center w-5 h-5 rounded-full bg-danger/15 text-danger">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m0 0l6-6m-6 6l-6-6" /></svg>
                                </span>
                                Saiu
                            </div>
                            <p class="money mt-1 text-lg font-bold text-danger break-words" x-text="money(totals.saiu)"></p>
                        </div>
                    </div>
                </div>

                {{-- Para onde foi o dinheiro: ranking de categorias com barras de proporção --}}
                <x-card title="Para onde foi o dinheiro" subtitle="Saídas por categoria no mês">
                    {{-- Com dados --}}
                    <div x-show="hasCategoryData" class="space-y-4">
                        {{-- Rosca com o total de saídas no centro --}}
                        <div class="relative h-52 sm:h-60">
                            <canvas x-ref="donutChart" aria-label="Gráfico de saídas por categoria" role="img"></canvas>
                        </div>

                        {{-- Ranking de categorias --}}
                        <ul class="space-y-3">
                            <template x-for="cat in categoryBreakdown" :key="cat.id">
                                <li>
                                    <div class="flex items-center gap-3">
                                        {{-- Ícone/cor da categoria --}}
                                        <span class="flex items-center justify-center w-9 h-9 rounded-xl shrink-0"
                                              :style="`background-color: ${cat.color}1F; color: ${cat.color}`"
                                              x-html="categoryIcon(cat.icon)"></span>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-baseline justify-between gap-2">
                                                <span class="font-medium text-[15px] text-neutral-800 dark:text-neutral-100 truncate" x-text="cat.name"></span>
                                                <span class="money font-bold text-neutral-800 dark:text-neutral-100 whitespace-nowrap" x-text="cat.moneyLabel"></span>
                                            </div>
                                            {{-- Barra de proporção da categoria --}}
                                            <div class="mt-1.5 flex items-center gap-2">
                                                <div class="flex-1 h-1.5 rounded-full overflow-hidden bg-neutral-200/60 dark:bg-neutral-700/40">
                                                    <div class="h-full rounded-full transition-all duration-500"
                                                         :style="`width: ${cat.pct}%; background-color: ${cat.color}`"></div>
                                                </div>
                                                <span class="text-xs font-medium text-neutral-400 dark:text-neutral-500 w-9 text-right" x-text="cat.pctLabel"></span>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            </template>
                        </ul>
                    </div>
                    {{-- Estado vazio --}}
                    <div x-show="!hasCategoryData" class="text-center py-10">
                        <p class="text-neutral-500 dark:text-neutral-400">Nenhuma saída registrada neste mês.</p>
                        <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-quick-add'))" class="btn-primary mt-4">
                            + Registrar uma saída
                        </button>
                    </div>
                </x-card>

                {{-- Necessidade vs. Desejo --}}
                <x-card title="Necessidade vs. desejo" subtitle="Proporção das saídas classificadas">
                    <template x-if="hasClassification">
                        <div class="space-y-4">
                            {{-- Barra de visão geral (composição empilhada) --}}
                            <div class="flex w-full h-2.5 rounded-full overflow-hidden bg-neutral-200/70 dark:bg-neutral-700/50">
                                <template x-for="row in needsVsWantsBreakdown" :key="row.key">
                                    <div class="h-full first:rounded-l-full last:rounded-r-full transition-all duration-500"
                                         :style="`width: ${row.pct}%; background-color: ${row.color}`"
                                         :title="`${row.name}: ${row.pctLabel}`"></div>
                                </template>
                            </div>

                            {{-- Linhas no mesmo padrão do ranking de categorias --}}
                            <ul class="space-y-3">
                                <template x-for="row in needsVsWantsBreakdown" :key="row.key">
                                    <li>
                                        <div class="flex items-center gap-3">
                                            <span class="flex items-center justify-center w-9 h-9 rounded-xl shrink-0"
                                                  :style="`background-color: ${row.color}1F; color: ${row.color}`"
                                                  x-html="categoryIcon(row.icon)"></span>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-baseline justify-between gap-2">
                                                    <span class="font-medium text-[15px] text-neutral-800 dark:text-neutral-100 truncate" x-text="row.name"></span>
                                                    <span class="money font-bold text-neutral-800 dark:text-neutral-100 whitespace-nowrap" x-text="row.moneyLabel"></span>
                                                </div>
                                                <div class="mt-1.5 flex items-center gap-2">
                                                    <div class="flex-1 h-1.5 rounded-full overflow-hidden bg-neutral-200/60 dark:bg-neutral-700/40">
                                                        <div class="h-full rounded-full transition-all duration-500"
                                                             :style="`width: ${row.pct}%; background-color: ${row.color}`"></div>
                                                    </div>
                                                    <span class="text-xs font-medium text-neutral-400 dark:text-neutral-500 w-9 text-right" x-text="row.pctLabel"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                </template>
                            </ul>

                            {{-- Nota de saídas sem classificação --}}
                            <template x-if="needsVsWants.sem_classificacao > 0">
                                <p class="text-xs text-neutral-400 dark:text-neutral-500 pt-1">
                                    <span x-text="money(needsVsWants.sem_classificacao)"></span> em saídas ainda não classificadas não entram nesta conta.
                                </p>
                            </template>
                        </div>
                    </template>
                    <template x-if="!hasClassification">
                        <p class="text-center text-neutral-500 dark:text-neutral-400 py-6">Classifique suas saídas como necessidade ou desejo para ver a proporção aqui.</p>
                    </template>
                </x-card>

                {{-- Evolução dos últimos meses: entrou vs. saiu --}}
                <x-card title="Sua evolução" subtitle="Entradas e saídas dos últimos 6 meses">
                    <div x-show="hasHistory" class="relative h-56 sm:h-64">
                        <canvas x-ref="historyChart" aria-label="Gráfico de evolução mensal de entradas e saídas" role="img"></canvas>
                    </div>
                    <p x-show="!hasHistory" class="text-center text-neutral-500 dark:text-neutral-400 py-6">
                        Registre suas transações para acompanhar a evolução mês a mês aqui.
                    </p>
                </x-card>
            </div>
        </template>
    </div>
</x-app-layout>
