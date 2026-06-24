{{-- Dashboard do mês: resumo entrou/saiu/sobrou, gráfico de rosca por categoria
     e proporção necessidade vs. desejo. Dados de /api/dashboard (valores em centavos). --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-neutral-800">Resumo do mês</h1>
                <p class="text-sm text-neutral-500">Quanto entrou, quanto saiu e o que sobrou</p>
            </div>
            <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-quick-add'))" class="btn-primary hidden sm:inline-flex">
                + Nova
            </button>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-5" x-data="dashboard" x-cloak>

        {{-- Seletor de mês --}}
        <div class="flex items-center justify-between">
            <button type="button" @click="prevMonth()" class="p-2 rounded-lg text-neutral-500 hover:bg-neutral-100" aria-label="Mês anterior">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
            </button>
            <span class="text-base font-semibold text-neutral-700 capitalize" x-text="monthLabel"></span>
            <button type="button" @click="nextMonth()" :disabled="!canGoNext"
                    class="p-2 rounded-lg text-neutral-500 hover:bg-neutral-100" :class="{ 'opacity-30 cursor-not-allowed': !canGoNext }" aria-label="Próximo mês">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
            </button>
        </div>

        {{-- Carregando --}}
        <div x-show="loading" class="text-center text-neutral-400 py-16">Carregando o resumo…</div>

        {{-- Erro --}}
        <div x-show="!loading && error" class="text-center py-12">
            <p class="text-danger" x-text="error"></p>
            <button type="button" @click="load()" class="btn-secondary mt-4">Tentar de novo</button>
        </div>

        <template x-if="!loading && !error">
            <div class="space-y-5">
                {{-- Cards de resumo --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    {{-- Entrou --}}
                    <div class="card !p-4">
                        <p class="text-xs font-medium text-neutral-500">Entrou</p>
                        <p class="mt-1 text-lg font-bold text-emerald-600 break-words" x-text="money(totals.entrou)"></p>
                    </div>
                    {{-- Saiu --}}
                    <div class="card !p-4">
                        <p class="text-xs font-medium text-neutral-500">Saiu</p>
                        <p class="mt-1 text-lg font-bold text-danger break-words" x-text="money(totals.saiu)"></p>
                    </div>
                    {{-- Sobrou --}}
                    <div class="card !p-4 col-span-2 sm:col-span-1">
                        <p class="text-xs font-medium text-neutral-500">Sobrou</p>
                        <p class="mt-1 text-lg font-bold break-words"
                           :class="totals.sobrou >= 0 ? 'text-brand-600' : 'text-danger'"
                           x-text="money(totals.sobrou)"></p>
                    </div>
                </div>

                {{-- Gráfico de rosca por categoria --}}
                <x-card title="Para onde foi o dinheiro" subtitle="Saídas por categoria no mês">
                    {{-- Com dados --}}
                    <div x-show="hasCategoryData" class="relative h-72">
                        <canvas x-ref="chartCanvas"></canvas>
                    </div>
                    {{-- Estado vazio --}}
                    <div x-show="!hasCategoryData" class="text-center py-10">
                        <p class="text-neutral-500">Nenhuma saída registrada neste mês.</p>
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
                            <div class="flex w-full h-4 rounded-full overflow-hidden bg-neutral-200">
                                <div class="bg-brand-600 h-full transition-all duration-500" :style="`width: ${needsVsWants.necessidade_pct}%`"></div>
                                <div class="bg-emerald-500 h-full transition-all duration-500" :style="`width: ${needsVsWants.desejo_pct}%`"></div>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full bg-brand-600"></span>
                                    <span class="text-neutral-700">Necessidade</span>
                                    <span class="font-semibold text-neutral-800" x-text="needsVsWants.necessidade_pct + '%'"></span>
                                    <span class="text-neutral-400" x-text="'(' + money(needsVsWants.necessidade) + ')'"></span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                                    <span class="text-neutral-700">Desejo</span>
                                    <span class="font-semibold text-neutral-800" x-text="needsVsWants.desejo_pct + '%'"></span>
                                    <span class="text-neutral-400" x-text="'(' + money(needsVsWants.desejo) + ')'"></span>
                                </div>
                            </div>
                            {{-- Nota de saídas sem classificação --}}
                            <template x-if="needsVsWants.sem_classificacao > 0">
                                <p class="text-xs text-neutral-400 pt-1">
                                    <span x-text="money(needsVsWants.sem_classificacao)"></span> em saídas ainda não classificadas não entram nesta conta.
                                </p>
                            </template>
                        </div>
                    </template>
                    <template x-if="!hasClassification">
                        <p class="text-center text-neutral-500 py-6">Classifique suas saídas como necessidade ou desejo para ver a proporção aqui.</p>
                    </template>
                </x-card>
            </div>
        </template>
    </div>
</x-app-layout>
