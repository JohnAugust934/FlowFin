{{-- Dashboard do mês: resumo entrou/saiu/sobrou, gráfico de rosca por categoria
     e proporção necessidade vs. desejo. Dados de /api/dashboard (valores em centavos).
     Estética de vidro fosco (glassmorphism / iOS 26) sobre o fundo ambiente. --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-neutral-800 dark:text-neutral-100">Resumo do mês</h1>
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
                    <p class="mt-1 text-4xl sm:text-5xl font-extrabold tracking-tight break-words"
                       :class="totals.sobrou >= 0 ? 'text-brand-600 dark:text-brand-300' : 'text-danger'"
                       x-text="money(totals.sobrou)"></p>
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
                            <p class="mt-1 text-lg font-bold text-emerald-600 dark:text-emerald-400 break-words" x-text="money(totals.entrou)"></p>
                        </div>
                        <div class="glass-row p-3">
                            <div class="flex items-center gap-1.5 text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                <span class="flex items-center justify-center w-5 h-5 rounded-full bg-danger/15 text-danger">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m0 0l6-6m-6 6l-6-6" /></svg>
                                </span>
                                Saiu
                            </div>
                            <p class="mt-1 text-lg font-bold text-danger break-words" x-text="money(totals.saiu)"></p>
                        </div>
                    </div>
                </div>

                {{-- Gráfico de rosca por categoria --}}
                <x-card title="Para onde foi o dinheiro" subtitle="Saídas por categoria no mês">
                    {{-- Com dados --}}
                    <div x-show="hasCategoryData" class="relative h-72">
                        <canvas x-ref="chartCanvas"></canvas>
                        {{-- Total no centro da rosca --}}
                        <div class="pointer-events-none absolute inset-x-0 top-[42%] -translate-y-1/2 text-center px-4">
                            <p class="text-[11px] uppercase tracking-wide text-neutral-400 dark:text-neutral-500">Total de saídas</p>
                            <p class="text-xl font-extrabold tracking-tight text-neutral-800 dark:text-neutral-100" x-text="totalSaidasLabel"></p>
                        </div>
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
                        <div class="space-y-3">
                            {{-- Barra de proporção --}}
                            <div class="flex w-full h-4 rounded-full overflow-hidden bg-neutral-200/70 dark:bg-neutral-700/60">
                                <div class="bg-brand-600 h-full transition-all duration-500" :style="`width: ${needsVsWants.necessidade_pct}%`"></div>
                                <div class="bg-emerald-500 h-full transition-all duration-500" :style="`width: ${needsVsWants.desejo_pct}%`"></div>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full bg-brand-600"></span>
                                    <span class="text-neutral-700 dark:text-neutral-300">Necessidade</span>
                                    <span class="font-semibold text-neutral-800 dark:text-neutral-100" x-text="needsVsWants.necessidade_pct + '%'"></span>
                                    <span class="text-neutral-400 dark:text-neutral-500" x-text="'(' + money(needsVsWants.necessidade) + ')'"></span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                                    <span class="text-neutral-700 dark:text-neutral-300">Desejo</span>
                                    <span class="font-semibold text-neutral-800 dark:text-neutral-100" x-text="needsVsWants.desejo_pct + '%'"></span>
                                    <span class="text-neutral-400 dark:text-neutral-500" x-text="'(' + money(needsVsWants.desejo) + ')'"></span>
                                </div>
                            </div>
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
            </div>
        </template>
    </div>
</x-app-layout>
