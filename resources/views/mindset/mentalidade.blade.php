{{-- Mentalidade (Pilar 4): Score FlowFin, streak de registro, dicas contextuais
     e mini-conteúdos educativos. Dados de /api/score, /api/streak, /api/tips e
     /api/educational-contents. Mesma linguagem visual do dashboard (vidro fosco,
     anel/barras em CSS, tema claro/escuro). --}}
<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="font-display text-xl font-bold text-neutral-800 dark:text-neutral-100">Mentalidade</h1>
            <p class="text-sm text-neutral-500 dark:text-neutral-400">Seu progresso e bons hábitos com o dinheiro</p>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-5" x-data="mentalidade" x-cloak>

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
        <div x-show="loading" class="glass p-10 text-center text-neutral-500 dark:text-neutral-400">Carregando seu progresso…</div>

        {{-- Erro --}}
        <div x-show="!loading && error" class="glass p-8 text-center">
            <p class="text-danger" x-text="error"></p>
            <button type="button" @click="load()" class="btn-secondary mt-4">Tentar de novo</button>
        </div>

        <template x-if="!loading && !error">
            <div class="space-y-5">

                {{-- Score FlowFin --}}
                <x-card title="Seu Score FlowFin" subtitle="De 0 a 100, o quanto você está cuidando bem do seu dinheiro">
                    <div class="flex flex-col sm:flex-row items-center gap-6">
                        {{-- Anel de progresso em SVG --}}
                        <div class="relative shrink-0 w-36 h-36">
                            <svg class="w-36 h-36 -rotate-90" viewBox="0 0 120 120">
                                <circle cx="60" cy="60" r="52" fill="none" stroke="currentColor" stroke-width="12" class="text-neutral-200/70 dark:text-neutral-700/50" />
                                <circle cx="60" cy="60" r="52" fill="none" :stroke="scoreColor" stroke-width="12" stroke-linecap="round"
                                        :stroke-dasharray="scoreDash" class="transition-all duration-700 ease-out" />
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-4xl font-extrabold tracking-tight text-neutral-800 dark:text-neutral-100" x-text="scoreValue"></span>
                                <span class="text-xs text-neutral-400 dark:text-neutral-500">de 100</span>
                            </div>
                        </div>

                        {{-- Leitura dos fatores --}}
                        <div class="flex-1 w-full space-y-3">
                            <p class="text-sm font-medium" :style="`color: ${scoreColor}`" x-text="scoreLabel"></p>
                            <template x-for="f in scoreFactors" :key="f.key">
                                <div>
                                    <div class="flex items-center justify-between gap-2 text-sm">
                                        <span class="font-medium text-neutral-700 dark:text-neutral-200" x-text="f.label"></span>
                                        <span class="text-xs text-neutral-400 dark:text-neutral-500">
                                            peso <span x-text="f.weightLabel"></span> ·
                                            <span class="font-semibold text-neutral-600 dark:text-neutral-300" x-text="f.valueLabel"></span>
                                        </span>
                                    </div>
                                    <div class="mt-1 w-full h-2 rounded-full overflow-hidden bg-neutral-200/70 dark:bg-neutral-700/50">
                                        <div class="h-full rounded-full bg-gradient-brand transition-all duration-500" :style="`width: ${f.width}%`"></div>
                                    </div>
                                    <p x-show="!f.included" class="mt-1 text-xs text-neutral-400 dark:text-neutral-500">
                                        Não entra na conta ainda. <template x-if="f.key === 'budgets'"><span>Defina um orçamento para incluir.</span></template><template x-if="f.key === 'savings_goal'"><span>Defina uma meta de economia para incluir.</span></template>
                                    </p>
                                </div>
                            </template>
                        </div>
                    </div>
                </x-card>

                {{-- Streak de registro --}}
                <x-card title="Sequência de registros" subtitle="Dias seguidos cuidando das suas finanças">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center justify-center w-16 h-16 rounded-2xl shrink-0 text-3xl"
                             :class="streakActive ? 'bg-warning-light' : 'bg-neutral-100 dark:bg-neutral-800 grayscale opacity-70'">
                            <span x-text="streakActive ? '🔥' : '💤'"></span>
                        </div>
                        <div class="min-w-0">
                            <p class="money text-2xl font-bold tracking-tight text-neutral-800 dark:text-neutral-100" x-text="streakLabel"></p>
                            <p class="text-sm text-neutral-500 dark:text-neutral-400">
                                <template x-if="streakActive"><span>Continue registrando para manter a chama acesa!</span></template>
                                <template x-if="!streakActive"><span>Registre uma transação hoje para começar uma nova sequência.</span></template>
                            </p>
                            <p x-show="lastActivityLabel" class="mt-0.5 text-xs text-neutral-400 dark:text-neutral-500">Último registro em <span x-text="lastActivityLabel"></span></p>
                        </div>
                    </div>
                </x-card>

                {{-- Dicas contextuais --}}
                <x-card title="Dicas para você" subtitle="Baseadas no seu mês">
                    <template x-if="hasTips">
                        <ul class="space-y-3">
                            <template x-for="t in tipRows" :key="t.key">
                                <li class="glass-row p-3">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="font-semibold text-[15px] text-neutral-800 dark:text-neutral-100" x-text="t.title"></span>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold shrink-0" :class="t.badgeClass">
                                            <span class="w-1.5 h-1.5 rounded-full" :class="t.dotClass"></span>
                                            <span x-text="t.levelLabel"></span>
                                        </span>
                                    </div>
                                    <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-300" x-text="t.message"></p>
                                </li>
                            </template>
                        </ul>
                    </template>
                    <template x-if="!hasTips">
                        <p class="text-center text-neutral-500 dark:text-neutral-400 py-6">Sem dicas por enquanto. Continue registrando suas transações.</p>
                    </template>
                </x-card>

                {{-- Mini-conteúdos educativos --}}
                <x-card title="Aprenda em 1 minuto" subtitle="Conteúdos rápidos sobre dinheiro">
                    {{-- Filtro por tema (aparece quando há temas conhecidos) --}}
                    <div x-show="themesSeen.length > 0" class="flex flex-wrap gap-2 mb-4">
                        <button type="button" @click="filterByTheme('')"
                                class="px-3 py-1 rounded-full text-xs font-medium transition"
                                :class="contentsTheme === '' ? 'bg-brand-600 text-white' : 'bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-300 hover:bg-neutral-200 dark:hover:bg-neutral-700'">Todos</button>
                        <template x-for="th in themesSeen" :key="th">
                            <button type="button" @click="filterByTheme(th)"
                                    class="px-3 py-1 rounded-full text-xs font-medium capitalize transition"
                                    :class="contentsTheme === th ? 'bg-brand-600 text-white' : 'bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-300 hover:bg-neutral-200 dark:hover:bg-neutral-700'"
                                    x-text="th"></button>
                        </template>
                    </div>

                    <div x-show="loadingContents" class="text-center py-6 text-neutral-500 dark:text-neutral-400">Carregando conteúdos…</div>

                    <template x-if="!loadingContents && hasContents">
                        <div class="space-y-3">
                            <template x-for="c in contents" :key="c.id">
                                <article class="glass-row p-4">
                                    <div class="flex items-center justify-between gap-2 mb-1">
                                        <h4 class="font-semibold text-[15px] text-neutral-800 dark:text-neutral-100" x-text="c.title"></h4>
                                        <span x-show="c.theme" class="px-2.5 py-1 rounded-full text-xs font-medium capitalize bg-brand-50 text-brand-700 dark:bg-brand-500/20 dark:text-brand-200 shrink-0" x-text="c.theme"></span>
                                    </div>
                                    <p class="text-sm text-neutral-600 dark:text-neutral-300 leading-relaxed" x-text="c.body"></p>
                                </article>
                            </template>

                            {{-- Paginação --}}
                            <div class="flex items-center justify-between pt-2" x-show="canPrevContents || canNextContents">
                                <button type="button" @click="prevContents()" :disabled="!canPrevContents"
                                        class="btn-secondary px-3 py-1.5 text-sm" :class="{ 'opacity-40 cursor-not-allowed': !canPrevContents }">Anterior</button>
                                <span class="text-xs text-neutral-400 dark:text-neutral-500">
                                    Página <span x-text="contentsMeta.current_page || 1"></span> de <span x-text="contentsMeta.last_page || 1"></span>
                                </span>
                                <button type="button" @click="nextContents()" :disabled="!canNextContents"
                                        class="btn-secondary px-3 py-1.5 text-sm" :class="{ 'opacity-40 cursor-not-allowed': !canNextContents }">Próxima</button>
                            </div>
                        </div>
                    </template>

                    <template x-if="!loadingContents && !hasContents">
                        <p class="text-center text-neutral-500 dark:text-neutral-400 py-6">Nenhum conteúdo disponível por enquanto.</p>
                    </template>
                </x-card>

            </div>
        </template>
    </div>
</x-app-layout>
