{{--
    PÁGINA DE DEMONSTRAÇÃO DO DESIGN SYSTEM — uso de desenvolvimento.
    Não é uma tela de produto. Serve para validar visualmente tema, layout base
    e a biblioteca de componentes do FlowFin. Acesse em /design-system.
--}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-neutral-800">Design System</h1>
                <p class="text-sm text-neutral-500">Página de demonstração (desenvolvimento)</p>
            </div>
            <x-badge status="neutral">DEV</x-badge>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-8">

        {{-- Banner com gradiente da marca --}}
        <section class="rounded-2xl bg-gradient-brand text-white p-6 sm:p-8 shadow-card">
            <div class="flex items-center gap-3">
                <x-brand-icon class="h-10 w-10 bg-white/15 rounded-xl p-1.5" />
                <div>
                    <h2 class="text-2xl font-extrabold tracking-tight">FlowFin</h2>
                    <p class="text-white/80 text-sm">Controle financeiro simples, claro e humano.</p>
                </div>
            </div>
        </section>

        {{-- PALETA --}}
        <section>
            <h2 class="text-lg font-semibold text-neutral-800 mb-3">Paleta de cores</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-card title="Azul da marca" subtitle="Confiança e estabilidade">
                    <div class="grid grid-cols-5 gap-1.5">
                        @foreach (['50','100','200','300','400','500','600','700','800','900'] as $s)
                            <div class="space-y-1">
                                <div class="h-10 rounded-md bg-brand-{{ $s }} border border-black/5"></div>
                                <p class="text-[10px] text-center text-neutral-500">{{ $s }}</p>
                            </div>
                        @endforeach
                    </div>
                </x-card>

                <x-card title="Verde esmeralda" subtitle="Crescimento e prosperidade">
                    <div class="grid grid-cols-5 gap-1.5">
                        @foreach (['50','100','200','300','400','500','600','700','800','900'] as $s)
                            <div class="space-y-1">
                                <div class="h-10 rounded-md bg-emerald-{{ $s }} border border-black/5"></div>
                                <p class="text-[10px] text-center text-neutral-500">{{ $s }}</p>
                            </div>
                        @endforeach
                    </div>
                </x-card>

                <x-card title="Cinza neutro" subtitle="Textos e superfícies">
                    <div class="grid grid-cols-5 gap-1.5">
                        @foreach (['50','100','200','300','400','500','600','700','800','900'] as $s)
                            <div class="space-y-1">
                                <div class="h-10 rounded-md bg-neutral-{{ $s }} border border-black/5"></div>
                                <p class="text-[10px] text-center text-neutral-500">{{ $s }}</p>
                            </div>
                        @endforeach
                    </div>
                </x-card>

                <x-card title="Cores semafóricas" subtitle="Status de metas e orçamentos">
                    <div class="grid grid-cols-3 gap-3">
                        <div class="space-y-1">
                            <div class="h-12 rounded-md bg-success"></div>
                            <p class="text-xs text-center text-neutral-600">Ok</p>
                        </div>
                        <div class="space-y-1">
                            <div class="h-12 rounded-md bg-warning"></div>
                            <p class="text-xs text-center text-neutral-600">Atenção</p>
                        </div>
                        <div class="space-y-1">
                            <div class="h-12 rounded-md bg-danger"></div>
                            <p class="text-xs text-center text-neutral-600">Estourado</p>
                        </div>
                    </div>
                </x-card>
            </div>
        </section>

        {{-- GRADIENTES --}}
        <section>
            <h2 class="text-lg font-semibold text-neutral-800 mb-3">Gradientes</h2>
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="h-24 rounded-2xl bg-gradient-brand flex items-end p-3 text-white text-sm font-medium">bg-gradient-brand</div>
                <div class="h-24 rounded-2xl bg-gradient-emerald flex items-end p-3 text-white text-sm font-medium">bg-gradient-emerald</div>
                <div class="h-24 rounded-2xl bg-gradient-brand-emerald flex items-end p-3 text-white text-sm font-medium">bg-gradient-brand-emerald</div>
            </div>
        </section>

        {{-- TIPOGRAFIA --}}
        <section>
            <h2 class="text-lg font-semibold text-neutral-800 mb-3">Tipografia — Inter</h2>
            <x-card>
                <div class="space-y-2">
                    <p class="text-3xl font-extrabold text-neutral-900">FlowFin <span class="text-neutral-400 text-base font-normal">— peso 800 (marca)</span></p>
                    <p class="text-xl font-bold">Quase tudo o que você precisa <span class="text-neutral-400 text-sm font-normal">— 700</span></p>
                    <p class="text-lg font-semibold">Resumo do mês <span class="text-neutral-400 text-sm font-normal">— 600</span></p>
                    <p class="text-base font-medium">Entradas e saídas <span class="text-neutral-400 text-sm font-normal">— 500</span></p>
                    <p class="text-base font-normal text-neutral-600">Texto corrido em linguagem simples e humana. <span class="text-neutral-400 text-sm">— 400</span></p>
                    <p class="pt-2 text-2xl font-bold text-emerald-600">R$ 1.234,56 <span class="text-neutral-400 text-sm font-normal">— valor em Real (formato brasileiro)</span></p>
                </div>
            </x-card>
        </section>

        {{-- BOTÕES --}}
        <section x-data="{ loading: false }">
            <h2 class="text-lg font-semibold text-neutral-800 mb-3">Botões</h2>
            <x-card>
                <div class="flex flex-wrap gap-3">
                    <x-button variant="primary">Salvar</x-button>
                    <x-button variant="secondary">Cancelar</x-button>
                    <x-button variant="success">Confirmar</x-button>
                    <x-button variant="danger">Excluir</x-button>
                    <x-button variant="primary" disabled>Desabilitado</x-button>
                </div>

                <div class="mt-4 pt-4 border-t border-neutral-100">
                    <p class="text-sm text-neutral-500 mb-2">Estado de carregamento (clique para simular):</p>
                    <button
                        type="button"
                        class="btn-primary"
                        :class="{ 'opacity-50 cursor-not-allowed': loading }"
                        :disabled="loading"
                        @click="loading = true; setTimeout(() => loading = false, 2000)"
                    >
                        <svg x-show="loading" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="loading ? 'Salvando…' : 'Salvar com loading'"></span>
                    </button>
                </div>
            </x-card>
        </section>

        {{-- CAMPOS / INPUTS --}}
        <section>
            <h2 class="text-lg font-semibold text-neutral-800 mb-3">Campos de formulário</h2>
            <x-card>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="Descrição" name="descricao_demo" placeholder="Ex.: Mercado da semana" hint="Use uma descrição curta e clara." />
                    <x-field label="Valor (R$)" name="valor_demo" type="text" inputmode="decimal" placeholder="0,00" />
                    <div>
                        <x-input-label value="Campo desabilitado" />
                        <x-text-input class="block w-full" value="Não editável" disabled />
                    </div>
                    <div>
                        <x-input-label value="Campo com erro" />
                        <x-text-input class="block w-full border-danger focus:border-danger focus:ring-danger" value="valor inválido" />
                        <p class="text-sm text-danger mt-1">Informe um valor maior que zero.</p>
                    </div>
                </div>
            </x-card>
        </section>

        {{-- CARDS --}}
        <section>
            <h2 class="text-lg font-semibold text-neutral-800 mb-3">Cards de resumo</h2>
            <div class="grid gap-4 sm:grid-cols-3">
                <x-card>
                    <p class="text-sm text-neutral-500">Entradas do mês</p>
                    <p class="text-2xl font-bold text-emerald-600 mt-1">R$ 4.200,00</p>
                </x-card>
                <x-card>
                    <p class="text-sm text-neutral-500">Saídas do mês</p>
                    <p class="text-2xl font-bold text-danger mt-1">R$ 3.150,00</p>
                </x-card>
                <x-card>
                    <p class="text-sm text-neutral-500">Saldo</p>
                    <p class="text-2xl font-bold text-brand-700 mt-1">R$ 1.050,00</p>
                </x-card>
            </div>
        </section>

        {{-- TOAST --}}
        <section>
            <h2 class="text-lg font-semibold text-neutral-800 mb-3">Toast — feedback imediato</h2>
            <x-card subtitle="Clique para disparar um aviso no canto da tela.">
                <div class="flex flex-wrap gap-3">
                    <x-button variant="success"
                        x-on:click="$dispatch('toast', { type: 'success', message: 'Transação registrada ✓' })">
                        Sucesso
                    </x-button>
                    <x-button variant="danger"
                        x-on:click="$dispatch('toast', { type: 'error', message: 'Não foi possível salvar. Tente de novo.' })">
                        Erro
                    </x-button>
                    <x-button variant="secondary"
                        x-on:click="$dispatch('toast', { type: 'warning', message: 'Você está perto do limite do orçamento.' })">
                        Aviso
                    </x-button>
                </div>
            </x-card>
        </section>

        {{-- BARRA DE PROGRESSO --}}
        <section>
            <h2 class="text-lg font-semibold text-neutral-800 mb-3">Barra de progresso</h2>
            <x-card>
                <div class="space-y-5">
                    <x-progress :value="45" label="Alimentação" caption="R$ 450 de R$ 1.000" />
                    <x-progress :value="85" label="Transporte" caption="R$ 425 de R$ 500" />
                    <x-progress :value="110" label="Lazer" caption="R$ 330 de R$ 300" />
                </div>
            </x-card>
        </section>

        {{-- BADGES --}}
        <section>
            <h2 class="text-lg font-semibold text-neutral-800 mb-3">Badges semafóricos</h2>
            <x-card>
                <div class="flex flex-wrap gap-3">
                    <x-badge status="success">Dentro do orçamento</x-badge>
                    <x-badge status="warning">Perto do limite</x-badge>
                    <x-badge status="danger">Estourado</x-badge>
                    <x-badge status="neutral">Sem meta</x-badge>
                </div>
            </x-card>
        </section>

    </div>
</x-app-layout>
